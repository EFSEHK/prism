<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppReleaseSetting extends Model
{
    protected $fillable = [
        'web_app_url',
        'android_apk_path',
        'android_version',
        'android_version_code',
        'ios_ipa_path',
        'ios_version',
        'ios_build_number',
        'release_notes',
    ];

    protected function casts(): array
    {
        return [
            'android_version_code' => 'integer',
            'ios_build_number' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'web_app_url' => config('releases.default_web_app_url'),
            'android_version' => '1.0.0',
            'android_version_code' => 1,
        ]);
    }

    public function androidApkUrl(): ?string
    {
        if (! $this->android_apk_path) {
            return null;
        }

        return url('/download/android').'?v='.$this->android_version_code;
    }

    public function iosIpaUrl(): ?string
    {
        if (! $this->ios_ipa_path) {
            return null;
        }

        return url('/download/ios').'?v='.($this->ios_build_number ?: '0');
    }
}
