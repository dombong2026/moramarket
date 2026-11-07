<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function valueOf(string $key, string $default = ''): string
    {
        return (string) (static::query()->where('key', $key)->value('value') ?? $default);
    }
}
