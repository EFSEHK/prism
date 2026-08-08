<?php

namespace Tests\Feature;

use App\Models\AppReleaseSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReleaseDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_version_returns_download_route_instead_of_storage_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('releases/sap-efsc-1.0.0.apk', 'fake-apk');

        AppReleaseSetting::query()->create([
            'android_apk_path' => 'releases/sap-efsc-1.0.0.apk',
            'android_version' => '1.0.0',
            'android_version_code' => 3,
        ]);

        $this->getJson('/api/mobile/version')
            ->assertOk()
            ->assertJsonPath('version', '1.0.0')
            ->assertJsonPath('version_code', 3)
            ->assertJsonPath('apk_url', url('/download/android').'?v=3');
    }

    public function test_android_download_streams_uploaded_apk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('releases/sap-efsc-1.0.0.apk', 'fake-apk-bytes');

        AppReleaseSetting::query()->create([
            'android_apk_path' => 'releases/sap-efsc-1.0.0.apk',
            'android_version' => '1.0.0',
            'android_version_code' => 1,
        ]);

        $response = $this->get('/download/android');

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.android.package-archive')
            ->assertHeader('content-disposition', 'attachment; filename=sap-efsc-1.0.0.apk');

        $this->assertSame(
            'fake-apk-bytes',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_android_download_is_not_found_when_apk_missing(): void
    {
        AppReleaseSetting::query()->create([
            'android_version' => '1.0.0',
            'android_version_code' => 1,
        ]);

        $this->get('/download/android')->assertNotFound();
    }
}
