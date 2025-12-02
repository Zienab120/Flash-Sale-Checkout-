<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookRequest\CreateWebhookRequest;
use App\Services\WebhookService;

class WebhookController extends Controller
{
    public function __construct(private WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    public function handlePaymentWebhook(CreateWebhookRequest $request)
    {
        try {
            $response =$this->webhookService->handlePaymentWebhook($request);
            return response()->json($response);
        } catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}
