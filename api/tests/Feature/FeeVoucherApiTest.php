<?php

namespace Tests\Feature;

use App\Models\FeeVoucher;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\User;
use Database\Seeders\NotificationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeeVoucherApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['accountant', 'parent', 'admin'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (['manage_fee_vouchers', 'view_fee_accounting'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('accountant', 'web')->givePermissionTo(['manage_fee_vouchers', 'view_fee_accounting']);

        $this->seed(NotificationCatalogSeeder::class);

        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
        $this->student = Student::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'study_group_id' => $this->studyGroup->id,
            'admission_no' => 'A-100',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_accountant_can_create_voucher_with_file(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->userWithRole('accountant'));

        $stored = UploadedFile::fake()->create('voucher.pdf', 100, 'application/pdf')
            ->store('fee-vouchers', 'public');

        $response = $this->postJson('/api/efsc/fee-vouchers', [
            'student_id' => $this->student->id,
            'title' => 'July fee',
            'file_path' => $stored,
        ]);

        $response->assertCreated()->assertJsonPath('title', 'July fee');
        $this->assertSame($stored, $response->json('file_path'));
        Storage::disk('public')->assertExists($stored);
    }

    public function test_parent_can_mark_submitted(): void
    {
        $voucher = FeeVoucher::query()->create([
            'student_id' => $this->student->id,
            'title' => 'July fee',
            'submission_status' => 'pending',
        ]);

        $parent = $this->userWithRole('parent');
        $parent->children()->attach($this->student->id);
        Sanctum::actingAs($parent);

        $this->patchJson("/api/efsc/fee-vouchers/{$voucher->id}/status", [
            'submission_status' => 'submitted',
        ])->assertOk()->assertJsonPath('submission_status', 'submitted');
    }

    public function test_parent_cannot_verify(): void
    {
        $voucher = FeeVoucher::query()->create([
            'student_id' => $this->student->id,
            'title' => 'July fee',
            'submission_status' => 'submitted',
        ]);

        $parent = $this->userWithRole('parent');
        $parent->children()->attach($this->student->id);
        Sanctum::actingAs($parent);

        $this->patchJson("/api/efsc/fee-vouchers/{$voucher->id}/status", [
            'submission_status' => 'verified',
        ])->assertStatus(422);
    }
}
