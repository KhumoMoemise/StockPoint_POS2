<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function business(): array
    {
        return [
            'business_name' => static::get('business_name', 'StockPoint Store'),
            'vat_number' => static::get('vat_number', ''),
            'phone' => static::get('phone', ''),
            'email' => static::get('email', ''),
            'address' => static::get('address', ''),
            'vat_rate' => (float) static::get('vat_rate', 15),
        ];
    }
}
