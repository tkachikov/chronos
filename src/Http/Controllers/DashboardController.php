<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\Factory;
use Tkachikov\Chronos\Enums\LastRunStateFilterEnum;
use Tkachikov\Chronos\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(Request $request): View|Factory
    {
        $refresh = $request->integer('refresh', 5);
        // A screen opened by a token has no session of the panel, so its rows stay plain text.
        $panelUrl = $request->query('token') ? null : route('chronos.main');

        return view('chronos::dashboard', [
            'dataUrl' => route('chronos.dashboard.data', array_filter([
                'token' => $request->query('token'),
            ])),
            'refresh' => max(1, min(300, $refresh)),
            'panelUrl' => $panelUrl,
            'runningUrl' => $panelUrl
                ? route('chronos.main', ['lastRunState' => LastRunStateFilterEnum::RUNNING->value])
                : null,
            'failedUrl' => $panelUrl
                ? route('chronos.main', ['lastRunState' => LastRunStateFilterEnum::FAILED->value])
                : null,
        ]);
    }

    public function data(DashboardService $dashboardService): JsonResponse
    {
        return response()->json($dashboardService->get());
    }
}
