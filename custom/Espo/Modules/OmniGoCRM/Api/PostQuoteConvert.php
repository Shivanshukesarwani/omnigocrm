<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\SalesService;

class PostQuoteConvert implements Action
{
    public function __construct(private SalesService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null || empty($data->quoteId) || !is_string($data->quoteId)) {
            throw new BadRequest('quoteId is required.');
        }

        $order = $this->service->convertQuote(trim($data->quoteId));

        return ResponseComposer::json([
            'accepted' => true,
            'orderId' => $order->getId(),
            'orderNumber' => $order->get('orderNumber'),
            'totalAmount' => $order->get('totalAmount'),
        ]);
    }
}
