<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * Handles API requests for user data.
 */
class UserController extends Controller
{
    /**
     * Return the authenticated user.
     *
     * @param Request $request
     * @return UserResource
     */
    public function show(Request $request): UserResource
    {
        $user = $request->user();

        return new UserResource($user);
    }
}
