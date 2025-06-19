<?php

namespace App\Http\Controllers;

use App\Services\StorageAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorageAnalyticsController extends Controller
{
    protected StorageAnalyticsService $storageAnalyticsService;
    
    public function __construct(StorageAnalyticsService $storageAnalyticsService)
    {
        $this->storageAnalyticsService = $storageAnalyticsService;
    }
    
    public function index(): Response
    {
        $overview = $this->storageAnalyticsService->getStorageOverview();
        
        return Inertia::render('StorageAnalytics', [
            'overview' => $overview,
        ]);
    }
    
    public function overview(): \Illuminate\Http\JsonResponse
    {
        $overview = $this->storageAnalyticsService->getStorageOverview();
        
        return response()->json($overview);
    }
    
    public function current(): \Illuminate\Http\JsonResponse
    {
        $metrics = $this->storageAnalyticsService->calculateCurrentMetrics();
        
        return response()->json($metrics);
    }
    
    public function historical(Request $request): \Illuminate\Http\JsonResponse
    {
        $days = $request->input('days', 30);
        $historical = $this->storageAnalyticsService->getHistoricalData($days);
        
        return response()->json([
            'data' => $historical,
            'days' => $days,
        ]);
    }
}
