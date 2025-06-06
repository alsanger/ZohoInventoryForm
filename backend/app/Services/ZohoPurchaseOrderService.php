<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoPurchaseOrderService extends ZohoBaseApiService
{
    /**
     * Создать новый заказ на закупку в Zoho Inventory.
     */
    public function createPurchaseOrder(array $purchaseOrderData): ?array
    {
        $requestData = $purchaseOrderData;

        $response = $this->zohoApiPost('/inventory/v1/purchaseorders', $requestData);

        if ($response && isset($response['purchaseorder'])) {
            Log::info('Successfully created Zoho Purchase Order.', ['purchaseorder_id' => $response['purchaseorder']['purchaseorder_id']]);
            return $response['purchaseorder'];
        }

        Log::error('Failed to create Zoho Purchase Order.', ['response' => $response, 'requestData' => $requestData]);
        return null;
    }
}
