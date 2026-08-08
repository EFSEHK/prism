<?php

namespace Database\Seeders;

use App\Models\AppReleaseSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class AppReleaseSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $apkPath = 'releases/sap-efsc-1.0.0.apk';
        if (! Storage::disk('public')->exists($apkPath)) {
            $apkPath = null;
        }

        AppReleaseSetting::query()->updateOrCreate(['id' => 1], [
            'web_app_url' => config('releases.default_web_app_url'),
            'android_apk_path' => $apkPath,
            'android_version' => '1.0.0',
            'android_version_code' => 1,
            'release_notes' => 'Initial release of the EFSC-YA school portal mobile app.',
        ]);
    }
}
