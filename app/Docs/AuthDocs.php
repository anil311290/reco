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
 *             @OA\Property(property="device_id", type="string", example="android-abc123", description="Unique device identifier for sync tracking"),
 *             @OA\Property(property="device_type", type="string", enum={"web","android","ios"}, example="android"),
 *             @OA\Property(property="device_name", type="string", example="Samsung Galaxy S24"),
 *             @OA\Property(property="device_os", type="string", example="Android 14"),
 *             @OA\Property(property="fcm_token", type="string", description="Firebase push token"),
 *             @OA\Property(property="push_token", type="string", description="Alternative push token")
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
 *     summary="Register new company owner",
 *     description="Create a new tenant company and owner account. Account status is pending until admin approval. User email is also used as company email. Do not send company_email.",
 *     operationId="register",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Registration data",
 *         @OA\JsonContent(
 *             required={"name", "email", "password", "password_confirmation", "company_name", "plan_slug"},
 *             @OA\Property(property="name", type="string", example="John Doe", description="Full name"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Owner email (also used as company email)"),
 *             @OA\Property(property="password", type="string", format="password", minLength=8, example="12345678", description="Password (min 8 characters)"),
 *             @OA\Property(property="password_confirmation", type="string", format="password", example="12345678", description="Confirm password"),
 *             @OA\Property(property="phone", type="string", example="+91 9876543210", description="Phone number"),
 *             @OA\Property(property="company_name", type="string", example="Acme Traders", description="Company / business name"),
 *             @OA\Property(property="plan_slug", type="string", example="trial", description="Required subscription plan slug from GET /plans")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Registration successful (pending approval)",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Registration successful. Your account is pending admin approval."),
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
