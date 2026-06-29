<?php

namespace App\OpenApi;

/**
 * @OA\Tag(
 *     name="Configuration",
 *     description="Application configuration key-value store"
 * )
 *
 * @OA\Get(
 *     path="/api/configurations",
 *     operationId="listConfigurations",
 *     tags={"Configuration"},
 *     summary="Get all configurations",
 *     description="Returns every configuration entry as key and value.",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of configurations",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="key", type="string", example="RPP_CONFIGURATION"),
 *                 @OA\Property(property="value", type="string", example="with_rpp")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated")
 * )
 *
 * @OA\Get(
 *     path="/api/configurations/{key}",
 *     operationId="getConfigurationByKey",
 *     tags={"Configuration"},
 *     summary="Get a single configuration by key",
 *     description="Returns one configuration entry for the given key.",
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="key",
 *         in="path",
 *         required=true,
 *         description="Configuration key",
 *         @OA\Schema(type="string", example="RPP_CONFIGURATION")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Configuration entry",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="key", type="string", pattern="^[A-Za-z0-9_]+$", example="RPP_CONFIGURATION"),
 *             @OA\Property(property="value", type="string", pattern="^[A-Za-z0-9_]+$", maxLength=255, example="with_rpp")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=404, description="Configuration not found")
 * )
 *
 * @OA\Post(
 *     path="/api/configurations",
 *     operationId="createConfiguration",
 *     tags={"Configuration"},
 *     summary="Create a configuration",
 *     description="Add a new configuration key. Key and value must contain only letters, numbers, and underscores. Value max length is 255.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"key", "value"},
 *             @OA\Property(property="key", type="string", pattern="^[A-Za-z0-9_]+$", example="RPP_CONFIGURATION"),
 *             @OA\Property(property="value", oneOf={
 *                 @OA\Schema(type="string", pattern="^[A-Za-z0-9_]+$", maxLength=255, example="with_rpp"),
 *                 @OA\Schema(type="boolean", example=true),
 *                 @OA\Schema(type="integer", example=11)
 *             })
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created configuration",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="key", type="string", pattern="^[A-Za-z0-9_]+$", example="RPP_CONFIGURATION"),
 *             @OA\Property(property="value", type="string", pattern="^[A-Za-z0-9_]+$", maxLength=255, example="with_rpp")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error (including duplicate key)")
 * )
 *
 * @OA\Put(
 *     path="/api/configurations",
 *     operationId="updateConfigurations",
 *     tags={"Configuration"},
 *     summary="Update configurations",
 *     description="Bulk upsert configuration entries by key.",
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"configurations"},
 *             @OA\Property(
 *                 property="configurations",
 *                 type="array",
 *                 minItems=1,
 *                 @OA\Items(
 *                     type="object",
 *                     required={"key", "value"},
 *                     @OA\Property(property="key", type="string", example="RPP_CONFIGURATION"),
 *                     @OA\Property(property="value", oneOf={
 *                         @OA\Schema(type="string", pattern="^[A-Za-z0-9_]+$", maxLength=255, example="with_rpp"),
 *                         @OA\Schema(type="boolean", example=true),
 *                         @OA\Schema(type="integer", example=11)
 *                     })
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Updated configurations",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="key", type="string"),
 *                 @OA\Property(property="value", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=422, description="Validation error")
 * )
 */
final class ConfigurationApiDoc
{
}
