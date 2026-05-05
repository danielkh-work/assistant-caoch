<?php

namespace App\OpenApi;

/**
 * OpenAPI root for the Human Dashboard collection (Postman).
 *
 * @OA\Info(
 *     title="Human Dashboard API",
 *     version="1.0.0",
 *     description="QB session login status and QB application logout. **Try it out** uses this deployment’s origin. For Postman, set `{{base_url}}` to the same host as the API (no trailing slash), e.g. `https://staging.admin.humandashboard.ca` — paths in this spec are already prefixed with `/api`."
 * )
 * @OA\Server(
 *     url="/",
 *     description="Relative to the host serving this OpenAPI document (staging, production, or local). No hardcoded localhost."
 * )
 */
final class HumanDashboardApiDoc
{
}
