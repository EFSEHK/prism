<?php

namespace App\Http\Controllers;

use App\Models\AppReleaseSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReleaseDownloadController extends Controller
{
    public function android(): BinaryFileResponse
    {
        $settings = AppReleaseSetting::current();

        return $this->download(
            $settings->android_apk_path,
            'application/vnd.android.package-archive',
            'Android APK is not available.'
        );
    }

    public function ios(): BinaryFileResponse
    {
        $settings = AppReleaseSetting::current();

        return $this->download(
            $settings->ios_ipa_path,
            'application/octet-stream',
            'iOS IPA is not available.'
        );
    }

    private function download(?string $path, string $contentType, string $missingMessage): BinaryFileResponse
    {
        abort_unless(is_string($path) && $path !== '' && Storage::disk('public')->exists($path), 404, $missingMessage);

        return response()->download(
            Storage::disk('public')->path($path),
            basename($path),
            ['Content-Type' => $contentType]
        );
    }
}
