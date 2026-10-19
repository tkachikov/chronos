<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\Factory;
use Tkachikov\Chronos\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(Request $request): View|Factory
    {
        $refresh = $request->integer('refresh', 5);

        return view('chronos::dashboard', [
            'dataUrl' => route('chronos.dashboard.data', array_filter([
                'token' => $request->query('token'),
            ])),
            'refresh' => max(1, min(300, $refresh)),
        ]);
    }

    public function data(DashboardService $dashboardService): JsonResponse
    {
        return response()->json($dashboardService->get());
    }
}
