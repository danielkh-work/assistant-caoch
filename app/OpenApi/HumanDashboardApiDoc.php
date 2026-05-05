<?php

namespace App\OpenApi;

/**
 * OpenAPI root for the Human Dashboard collection (Postman).
 *
 * @OA\Info(
 *     title="Human Dashboard API",
 *     version="1.0.0",
 *     description="QB session login status and QB application logout. Postman `{{base_url}}` should match `L5_SWAGGER_CONST_HOST` + `/api` paths below (e.g. `http://localhost:8000/api` → set host to `http://localhost:8000`)."
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Application base URL (no trailing slash). Override with `L5_SWAGGER_CONST_HOST` in `.env`."
 * )
 */
final class HumanDashboardApiDoc
{
}
