<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/** Calendar dates stored and exposed as Y-m-d in the app timezone (Asia/Karachi). */
class AppDate implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $date = $this->normalizeDateString($value);

        return Carbon::createFromFormat('Y-m-d', $date, config('app.timezone'))->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeDateString($value);
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $raw = $attributes[$key] ?? null;

        if ($raw === null) {
            return null;
        }

        return $this->normalizeDateString($raw);
    }

    private function normalizeDateString(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone(config('app.timezone'))->format('Y-m-d');
        }

        $string = (string) $value;

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $string, $matches)) {
            return $matches[1];
        }

        return Carbon::parse($string, config('app.timezone'))->timezone(config('app.timezone'))->format('Y-m-d');
    }
}
