<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Tkachikov\Chronos\Services\HealthService;

final class HealthController extends Controller
{
    public function __invoke(HealthService $healthService): JsonResponse
    {
        $health = $healthService->check();

        return response()->json(
            $health,
            $health['status'] === 'ok' ? 200 : 503,
        );
    }
}
