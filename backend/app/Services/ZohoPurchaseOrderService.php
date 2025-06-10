<?php
/*
namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoPurchaseOrderService extends ZohoBaseApiService
{
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
}*/


namespace App\Services;

use Illuminate\Support\Facades\Log;

class ZohoPurchaseOrderService
{
    public function createPurchaseOrder(array $purchaseOrderData): ?array
    {
        $response = ZohoBaseApiService::getInstance()
            ->reset()
            ->setMethod('post')
            ->setEndpoint('/inventory/v1/purchaseorders')
            ->setBody($purchaseOrderData)
            ->build();

        if ($response && isset($response['purchaseorder'])) {
            Log::info('Successfully created Zoho Purchase Order.', ['purchaseorder_id' => $response['purchaseorder']['purchaseorder_id']]);
            return $response['purchaseorder'];
        }

        Log::error('Failed to create Zoho Purchase Order.', ['response' => $response, 'requestData' => $purchaseOrderData]);
        return null;
    }
}
