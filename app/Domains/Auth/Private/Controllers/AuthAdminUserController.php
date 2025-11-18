<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Auth\Private\Services\UserService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Domains\Auth\Private\Models\User;

class AuthAdminUserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function deactivate(User $user): Response
    {
        $this->userService->deactivateUser($user);

        return response()->noContent();
    }

    public function reactivate(User $user): Response
    {
        $this->userService->activateUser($user);

        return response()->noContent();
    }
}
