<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\WorkspaceMemberService;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;
class PostOrderItemAdd implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private WorkspaceService $workspaceService,
        private WorkspaceMemberService $memberService,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();
        $workspaceId = $this->workspaceService->currentId();

        if (!$workspaceId) {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        $this->memberService->activeMembership($workspaceId);

        if (empty($data->orderId)) {
            throw new BadRequest('orderId is required.');
        }

        $orderId = trim($data->orderId);
        $order = $this->entityManager->getEntityById('Order', $orderId);

        if (!$order || (string) $order->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new BadRequest('Order not found in the active workspace.');
        }

        $item = $this->entityManager->getNewEntity('OrderItem');

        $quantity = (float) ($data->quantity ?? 1);
        $unitPrice = (float) ($data->unitPrice ?? 0);

        if ($quantity <= 0 || $unitPrice < 0) {
            throw new BadRequest('Invalid quantity or unitPrice.');
        }

        $discount = max(0, (float) ($data->discountAmount ?? 0));
        $tax = max(0, (float) ($data->taxAmount ?? 0));
        $item->setMultiple([
            'name' => isset($data->name) && is_string($data->name) ? trim($data->name) : 'Item',
            'orderId' => $orderId,
            'productId' => isset($data->productId) && is_string($data->productId) ? trim($data->productId) : null,
            'description' => isset($data->description) && is_string($data->description) ? trim($data->description) : null,
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'discountAmount' => $discount,
            'taxAmount' => $tax,
            'lineTotal' => max(0, $quantity * $unitPrice - $discount + $tax),
            'currency' => isset($data->currency) && is_string($data->currency) ? trim($data->currency) : 'INR',
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);

        $this->entityManager->saveEntity($item);

        $orderItems = $this->entityManager->getRDBRepository('OrderItem')->where([
            'orderId' => $orderId,
            'deleted' => false,
        ])->find();

        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($orderItems as $line) {
            $qty = (float) $line->get('quantity');
            $price = (float) $line->get('unitPrice');
            $discountTotal += max(0, (float) $line->get('discountAmount'));
            $taxTotal += max(0, (float) $line->get('taxAmount'));
            $subtotal += $qty * $price;
        }

        $order->setMultiple([
            'subtotal' => $subtotal,
            'discountAmount' => $discountTotal,
            'taxAmount' => $taxTotal,
            'totalAmount' => max(0, $subtotal - $discountTotal + $taxTotal),
        ]);
        $this->entityManager->saveEntity($order);

        return ResponseComposer::json([
            'accepted' => true,
            'orderItemId' => $item->getId(),
            'lineTotal' => $item->get('lineTotal'),
            'orderTotal' => $order->get('totalAmount'),
        ]);
    }
}
