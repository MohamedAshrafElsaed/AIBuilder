<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * Handles user-related API endpoints.
 */
class UserController extends Controller
{
    /**
     * Get the authenticated user's information.
     *
     * @param Request $request
     * @return UserResource
     */
    public function show(Request $request): UserResource
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        return new UserResource($user);
    }
}
