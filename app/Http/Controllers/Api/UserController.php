<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * Handles user-related API endpoints.
 */
class UserController extends Controller
{
    /**
     * Get the authenticated user's information.
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return (new UserResource($user))->response();
    }
}
