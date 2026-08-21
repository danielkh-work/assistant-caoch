<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Assistant Coaches",
 *     description="Head coach management of assistant and performance coaches"
 * )
 *
 * @OA\Schema(
 *     schema="AssistantCoach",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=12),
 *     @OA\Property(property="name", type="string", example="Albert"),
 *     @OA\Property(property="email", type="string", format="email", example="albert@albert.com"),
 *     @OA\Property(property="role", type="string", enum={"assistant_coach", "performance_coach"}, example="assistant_coach"),
 *     @OA\Property(property="status", type="string", enum={"approved", "inactive", "pending", "rejected"}, example="approved"),
 *     @OA\Property(property="head_coach_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="AssistantCoachPagination",
 *     type="object",
 *     @OA\Property(property="total", type="integer", example=25),
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="per_page", type="integer", example=20),
 *     @OA\Property(property="last_page", type="integer", example=2)
 * )
 *
 * @OA\Get(
 *     path="/api/assistant-coaches",
 *     operationId="listAssistantCoaches",
 *     tags={"Assistant Coaches"},
 *     summary="List assistant coaches",
 *     description="Returns assistant and performance coaches created by the authenticated head coach. Pass page or per_page to enable pagination.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number (enables pagination when provided with or without per_page)",
 *         @OA\Schema(type="integer", example=1, default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         description="Items per page (max 500; enables pagination when provided with or without page)",
 *         @OA\Schema(type="integer", example=20, default=20)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach list",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="assistant coach list"),
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AssistantCoach")),
 *             @OA\Property(property="pagination", ref="#/components/schemas/AssistantCoachPagination")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches")
 * )
 *
 * @OA\Post(
 *     path="/api/assistant-coaches",
 *     operationId="createAssistantCoach",
 *     tags={"Assistant Coaches"},
 *     summary="Create assistant coach",
 *     description="Creates an assistant or performance coach under the authenticated head coach. New coaches are created with status approved so they can log in immediately.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email", "password", "role"},
 *             @OA\Property(property="name", type="string", example="Albert"),
 *             @OA\Property(property="email", type="string", format="email", example="albert@albert.com"),
 *             @OA\Property(property="password", type="string", format="password", example="password123"),
 *             @OA\Property(property="role", type="string", enum={"assistant_coach", "performance_coach"}, example="assistant_coach")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach created",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Add Assistant Coach Successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/AssistantCoach")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Get(
 *     path="/api/assistant-coaches/{id}",
 *     operationId="showAssistantCoach",
 *     tags={"Assistant Coaches"},
 *     summary="Get assistant coach",
 *     description="Retrieve a single assistant or performance coach owned by the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Assistant coach user ID",
 *         @OA\Schema(type="integer", example=12)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Assistant coach retrieved successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/AssistantCoach")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches"),
 *     @OA\Response(response=404, description="Assistant coach not found")
 * )
 *
 * @OA\Put(
 *     path="/api/assistant-coaches/{id}",
 *     operationId="updateAssistantCoach",
 *     tags={"Assistant Coaches"},
 *     summary="Update assistant coach",
 *     description="Update an assistant or performance coach owned by the authenticated head coach.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Assistant coach user ID",
 *         @OA\Schema(type="integer", example=12)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="name", type="string", example="Albert Updated"),
 *             @OA\Property(property="email", type="string", format="email", example="albert.updated@albert.com"),
 *             @OA\Property(property="password", type="string", format="password", nullable=true, example="newpassword123"),
 *             @OA\Property(property="role", type="string", enum={"assistant_coach", "performance_coach"}, example="performance_coach")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Assistant coach updated successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/AssistantCoach")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches"),
 *     @OA\Response(response=404, description="Assistant coach not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Patch(
 *     path="/api/assistant-coaches/{id}/status",
 *     operationId="updateAssistantCoachStatus",
 *     tags={"Assistant Coaches"},
 *     summary="Activate or deactivate assistant coach",
 *     description="Set assistant coach status to approved (active) or inactive. Inactive coaches cannot log in and existing tokens are revoked.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Assistant coach user ID",
 *         @OA\Schema(type="integer", example=12)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"status"},
 *             @OA\Property(property="status", type="string", enum={"approved", "inactive"}, example="inactive")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach status updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Assistant coach status updated successfully"),
 *             @OA\Property(property="data", ref="#/components/schemas/AssistantCoach")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches"),
 *     @OA\Response(response=404, description="Assistant coach not found"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 *
 * @OA\Delete(
 *     path="/api/assistant-coaches/{id}",
 *     operationId="deleteAssistantCoach",
 *     tags={"Assistant Coaches"},
 *     summary="Delete assistant coach",
 *     description="Permanently delete an assistant or performance coach owned by the authenticated head coach. Revokes all active tokens first.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Assistant coach user ID",
 *         @OA\Schema(type="integer", example=12)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Assistant coach deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Assistant coach deleted successfully")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=403, description="Only head coaches may manage assistant coaches"),
 *     @OA\Response(response=404, description="Assistant coach not found")
 * )
 */
class AssistantCoachApiDoc
{
}
