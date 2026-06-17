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
 */
final class HumanDashboardApiDoc
{
}
