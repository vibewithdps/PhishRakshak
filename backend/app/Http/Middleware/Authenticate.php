<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * API project hai, isliye unauthenticated request ko missing web "login"
     * route par redirect nahi karna. Laravel JSON 401 return karega.
     *
     * Important: Laravel 9 parent method me type-hint nahi hai, isliye
     * yaha Request type aur ?string return type nahi lagana.
     */
    protected function redirectTo($request)
    {
        return null;
    }
}