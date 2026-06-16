<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppReleaseSetting;
use Illuminate\Http\JsonResponse;

class MobileVersionController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = AppReleaseSetting::current();

        return response()->json([
            'version' => $settings->android_version,
            'version_code' => $settings->android_version_code,
            'apk_url' => $settings->androidApkUrl(),
            'release_notes' => $settings->release_notes,
            'ios_version' => $settings->ios_version,
            'ios_build_number' => $settings->ios_build_number,
            'ipa_url' => $settings->iosIpaUrl(),
        ]);
    }
}
