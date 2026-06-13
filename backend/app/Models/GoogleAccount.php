<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class GoogleAccount extends Model
{
    protected $fillable = [
        'user_id',
        'google_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scope',
        'protection_mode',
        'trash_threshold',
        'scan_limit',
        'last_scan_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_scan_at' => 'datetime',
        'trash_threshold' => 'float',
        'scan_limit' => 'integer',
    ];

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
