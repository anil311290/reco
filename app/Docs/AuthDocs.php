<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Post(
 *     path="/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     description="Authenticate user with email and password to get access token",
 *     operationId="login",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Login credentials",
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="superadmin@reco.app", description="User email address"),
 *             @OA\Property(property="password", type="string", format="password", minLength=6, example="12345678", description="User password"),
 *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro", description="Device name for token identification")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="data", type="object",
 *                 @OA\Property(property="user", ref="#/components/schemas/User"),
 *                 @OA\Property(property="token", type="string", example="1|abc123def456...", description="Sanctum access token")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/register",
 *     tags={"Authentication"},
 *     summary="Register new user",
 *     description="Create a new user account",
 *     operationId="register",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Registration data",
 *         @OA\JsonContent(
 *             required={"name", "email", "password", "password_confirmation"},
 *             @OA\Property(property="name", type="string", example="John Doe", description="Full name"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
 *             @OA\Property(property="password", type="string", format="password", minLength=8, example="12345678", description="Password (min 8 characters)"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="12345678", description="Confirm password"),
 *             @OA\Property(property="phone", type="string", example="+91 9876543210", description="Phone number"),
 *             @OA\Property(property="company_id", type="integer", example=1, description="Company ID")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Registration successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Registration successful"),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Post(
 *     path="/logout",
 *     tags={"Authentication"},
 *     summary="Logout user",
 *     description="Revoke current access token",
 *     operationId="logout",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Logout successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Logged out successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/me",
 *     tags={"Authentication"},
 *     summary="Get authenticated user",
 *     description="Get current authenticated user details",
 *     operationId="getMe",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="User details",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Put(
 *     path="/profile",
 *     tags={"Authentication"},
 *     summary="Update user profile",
 *     description="Update current user profile information",
 *     operationId="updateProfile",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         description="Profile data to update",
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="John Doe Updated"),
 *             @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
 *             @OA\Property(property="phone", type="string", example="+91 9876543211")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profile updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 *
 * @OA\Put(
 *     path="/change-password",
 *     tags={"Authentication"},
 *     summary="Change password",
 *     description="Change current user password",
 *     operationId="changePassword",
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Password data",
 *         @OA\JsonContent(
 *             required={"current_password", "password", "password_confirmation"},
 *             @OA\Property(property="current_password", type="string", format="password", example="old12345678", description="Current password"),
 *             @OA\Property(property="password", type="string", format="password", minLength=8, example="new12345678", description="New password"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="new12345678", description="Confirm new password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password changed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Password changed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
class AuthDocs
{
    //
}
