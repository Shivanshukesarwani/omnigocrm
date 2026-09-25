<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\SalesService;

class PostPaymentCreate implements Action
{
    public function __construct(private SalesService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $payment = $this->service->recordPayment((array) $data);

        return ResponseComposer::json([
            'accepted' => true,
            'paymentId' => $payment->getId(),
            'paymentNumber' => $payment->get('paymentNumber'),
            'status' => $payment->get('status'),
            'amount' => $payment->get('amount'),
        ]);
    }
}
