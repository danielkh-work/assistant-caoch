<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Human Dashboard API",
 *     version="1.0.0"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Sanctum bearer token. Use format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="QB Logout",
 *     description="QB application logout and session login status"
 * )
 *
 * @OA\Get(
 *     path="/api/logout-qb-applicaion/{id}",
 *     operationId="logoutQbApplication",
 *     tags={"QB Logout"},
 *     summary="QB application logout",
 *     description="Clears `session_id` and `is_loggin` for the QB user. Postman: **QB Logout Success**.",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="QB user primary key",
 *         @OA\Schema(type="integer", example=78)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Logout succeeded, or user was not found (see `status` and `message` in the JSON body; HTTP code may still be 200).",
 *         @OA\JsonContent(
 *             oneOf={
 *                 @OA\Schema(
 *                     @OA\Property(property="status", type="integer", example=200),
 *                     @OA\Property(property="message", type="string", example="logout successful"),
 *                     @OA\Property(
 *                         property="user",
 *                         type="object",
 *                         @OA\Property(property="name", type="string"),
 *                         @OA\Property(property="session_id", type="string", nullable=true),
 *                         @OA\Property(property="code", type="string", nullable=true),
 *                         @OA\Property(property="head_coach_id", type="integer", nullable=true)
 *                     )
 *                 ),
 *                 @OA\Schema(
 *                     @OA\Property(property="status", type="integer", example=404),
 *                     @OA\Property(property="message", type="string", example="User not found")
 *                 )
 *             }
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/qb-session-login-status/{session_id}",
 *     operationId="qbSessionLoginStatus",
 *     tags={"QB Logout"},
 *     summary="Check QB login status",
 *     description="Returns **200** when a QB user is bound to the given mobile session UUID. Returns **401** when no QB has this `session_id` (Postman **Check QB Login Status 200** / invalid session **Check QB Login Status 403** — API uses **401 Unauthenticated**).",
 *     @OA\Parameter(
 *         name="session_id",
 *         in="path",
 *         required=true,
 *         description="Mobile pairing session UUID from `POST /api/mobile/create-session`",
 *         @OA\Schema(type="string", format="uuid", example="822fc835-75aa-48bf-8473-354a4913aab2")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="QB is linked to this session",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="session_id", type="string", format="uuid"),
 *             @OA\Property(property="logged_in", type="boolean", description="Mirrors the QB user's `is_loggin` flag in the database.", example=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated — no QB user with this session_id",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=401),
 *             @OA\Property(property="message", type="string", example="Unauthenticated")
 *         )
 *     )
 * )
 */
final class HumanDashboardApiDoc
{
}
