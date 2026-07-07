<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = '/'.trim($redirect->from_path, '/');
            $redirect->to_path = trim($redirect->to_path);

            if ($redirect->from_path !== '/' && str_ends_with($redirect->from_path, '/')) {
                $redirect->from_path = rtrim($redirect->from_path, '/');
            }
        });
    }
}
