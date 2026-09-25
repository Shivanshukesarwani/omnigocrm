<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\OmniGoCRM\Services\SalesService;

class PostQuoteCreate implements Action
{
    public function __construct(private SalesService $service) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        if ($data === null) throw new BadRequest('A JSON payload is required.');

        $quote = $this->service->createQuote((array) $data);

        return ResponseComposer::json([
            'accepted' => true,
            'quoteId' => $quote->getId(),
            'quoteNumber' => $quote->get('quoteNumber'),
            'status' => $quote->get('status'),
            'totalAmount' => $quote->get('totalAmount'),
        ]);
    }
}
