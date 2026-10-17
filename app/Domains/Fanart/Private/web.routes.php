<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . implode(',', [
    Roles::USER_CONFIRMED,
    Roles::MODERATOR,
    Roles::ADMIN,
    Roles::TECH_ADMIN,
])])->group(function () {
    // Routes land here from phase 8 onward.
});
