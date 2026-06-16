<?php

namespace App\Http\Controllers;

use App\Models\AppReleaseSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DevPortalController extends Controller
{
    public function showLogin(): View
    {
        return view('dev-portal.login');
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        if (! $user->hasAnyRole(['superadmin', 'developer'])) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => ['Only developer or superadmin accounts may access this portal.'],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('dev-portal.settings');
    }

    public function showSettings(): View
    {
        $settings = AppReleaseSetting::current();

        return view('dev-portal.settings', [
            'settings' => $settings,
            'androidApkUrl' => $settings->androidApkUrl(),
            'iosIpaUrl' => $settings->iosIpaUrl(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'web_app_url' => ['nullable', 'url', 'max:2048'],
            'android_version' => ['required', 'string', 'max:32'],
            'android_version_code' => ['required', 'integer', 'min:1'],
            'ios_version' => ['nullable', 'string', 'max:32'],
            'ios_build_number' => ['nullable', 'integer', 'min:1'],
            'release_notes' => ['nullable', 'string', 'max:5000'],
            'android_apk' => ['nullable', 'file', 'mimes:apk', 'max:204800'],
            'ios_ipa' => ['nullable', 'file', 'mimes:ipa', 'max:512000'],
        ]);

        $settings = AppReleaseSetting::current();

        if ($request->hasFile('android_apk')) {
            if ($settings->android_apk_path) {
                Storage::disk('public')->delete($settings->android_apk_path);
            }

            $versionSlug = str_replace('.', '-', $validated['android_version']);
            $path = $request->file('android_apk')->storeAs(
                'releases',
                'EFSC-YA-android-'.$versionSlug.'.apk',
                'public'
            );
            $settings->android_apk_path = $path;
        }

        if ($request->hasFile('ios_ipa')) {
            if ($settings->ios_ipa_path) {
                Storage::disk('public')->delete($settings->ios_ipa_path);
            }

            $iosVersion = $validated['ios_version'] ?? 'draft';
            $versionSlug = str_replace('.', '-', $iosVersion);
            $path = $request->file('ios_ipa')->storeAs(
                'releases',
                'EFSC-YA-ios-'.$versionSlug.'.ipa',
                'public'
            );
            $settings->ios_ipa_path = $path;
        }

        $settings->fill([
            'web_app_url' => $validated['web_app_url'] ?? null,
            'android_version' => $validated['android_version'],
            'android_version_code' => $validated['android_version_code'],
            'ios_version' => $validated['ios_version'] ?? null,
            'ios_build_number' => $validated['ios_build_number'] ?? null,
            'release_notes' => $validated['release_notes'] ?? null,
        ]);
        $settings->save();

        return redirect()
            ->route('dev-portal.settings')
            ->with('status', 'Release settings saved successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome');
    }
}
