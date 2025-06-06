<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZohoAuthService;
use App\Services\ZohoItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZohoItemController extends Controller
{
    protected ZohoItemService $zohoItemService;
    protected ZohoAuthService $zohoAuthService;

    public function __construct(ZohoItemService $zohoItemService, ZohoAuthService $zohoAuthService)
    {
        $this->zohoItemService = $zohoItemService;
        $this->zohoAuthService = $zohoAuthService;
    }

    /**
     * Получить список товаров из Zoho Inventory.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('search') && !empty($request->input('search'))) {
            $filters['search_text'] = $request->input('search');
        }

        $itemsData = $this->zohoItemService->getItems($filters);

        if (empty($itemsData['items']) && !empty($request->input('search'))) {
            Log::info('Товары Zoho не найдены по запросу.', ['search' => $filters['search_text']]);
        } elseif (empty($itemsData['items'])) {
            Log::warning('Товары Zoho не найдены.');
        }

        return response()->json([
            'success' => true,
            'items' => $itemsData['items'],
            'page_context' => $itemsData['page_context'] ?? []
        ]);
    }
}
