<?php

namespace Tests\Feature;

use App\Models\DatesheetEntry;
use App\Models\NotificationFeature;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Support\NotificationFeatureKeys;
use Database\Seeders\NotificationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableApiTest extends TestCase
{
    use RefreshDatabase;

    private StudyGroup $studyGroup;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['computer_operator', 'parent', 'teacher'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        Permission::findOrCreate('manage_timetable', 'web');
        Permission::findOrCreate('view_parent_dashboard', 'web');
        Role::findByName('computer_operator', 'web')->givePermissionTo(['manage_timetable']);
        Role::findByName('teacher', 'web')->givePermissionTo(['manage_timetable']);
        Role::findByName('parent', 'web')->givePermissionTo(['view_parent_dashboard']);

        $this->seed(NotificationCatalogSeeder::class);

        $this->studyGroup = StudyGroup::query()->create(['name' => 'Group A']);
        $this->subject = Subject::query()->create(['name' => 'English']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_operator_can_create_slot(): void
    {
        Sanctum::actingAs($this->userWithRole('computer_operator'));

        $this->postJson('/api/efsc/timetable/slots', [
            'study_group_id' => $this->studyGroup->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '09:45',
            'room' => 'A1',
        ])->assertCreated()->assertJsonPath('room', 'A1');

        $this->assertDatabaseHas('timetable_slots', [
            'study_group_id' => $this->studyGroup->id,
            'day_of_week' => 1,
        ]);
    }

    public function test_datesheet_can_notify_parents(): void
    {
        Sanctum::actingAs($this->userWithRole('computer_operator'));

        Student::query()->create([
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'study_group_id' => $this->studyGroup->id,
        ]);

        $response = $this->postJson('/api/efsc/timetable/datesheet', [
            'title' => 'Math exam',
            'exam_date' => '2026-09-01',
            'notes' => 'Bring calculator',
            'notify_parents' => true,
        ]);

        $response->assertCreated();
        $entryId = $response->json('id');

        $feature = NotificationFeature::query()
            ->where('feature_key', NotificationFeatureKeys::TIMETABLE_DATESHEET)
            ->first();
        $this->assertNotNull($feature);
        $this->assertDatabaseHas('notification_dispatch_requests', [
            'notification_feature_id' => $feature->id,
            'context_type' => 'DatesheetEntry',
            'context_id' => $entryId,
        ]);
    }

    public function test_parent_can_list_slots_for_child_group(): void
    {
        TimetableSlot::query()->create([
            'study_group_id' => $this->studyGroup->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '10:45',
        ]);

        $other = StudyGroup::query()->create(['name' => 'Other']);
        TimetableSlot::query()->create([
            'study_group_id' => $other->id,
            'day_of_week' => 2,
            'start_time' => '11:00',
            'end_time' => '11:45',
        ]);

        $parent = $this->userWithRole('parent');
        $child = Student::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'study_group_id' => $this->studyGroup->id,
        ]);
        $parent->children()->attach($child->id);
        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/efsc/timetable/slots');
        $response->assertOk();
        $groupIds = collect($response->json('data'))->pluck('study_group_id')->unique()->all();
        $this->assertSame([$this->studyGroup->id], $groupIds);
    }
}
