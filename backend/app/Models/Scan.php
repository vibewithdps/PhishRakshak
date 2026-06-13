<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'content',
        'is_phishing',
        'confidence',
        'category',
        'explanation',
    ];
}