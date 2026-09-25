<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity;

class SalesService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function createQuote(array $data): Entity
    {
        $workspaceId = $this->workspaceId();
        $name = $this->text($data, 'name');

        if ($name === '') {
            $name = 'Quote ' . gmdate('Y-m-d H:i:s');
        }

        $quote = $this->entityManager->getNewEntity('Quote');

        $quote->set([
            'name' => $name,
            'quoteNumber' => $this->nextNumber('Quote', 'quoteNumber', 'Q-' . gmdate('Y') . '-'),
            'status' => 'Draft',
            'issueDate' => $this->date($data, 'issueDate') ?: gmdate('Y-m-d'),
            'validUntil' => $this->date($data, 'validUntil'),
            'currency' => $this->text($data, 'currency') ?: 'INR',
            'leadId' => $this->nullableId($data, 'leadId'),
            'contactId' => $this->nullableId($data, 'contactId'),
            'accountId' => $this->nullableId($data, 'accountId'),
            'opportunityId' => $this->nullableId($data, 'opportunityId'),
            'notes' => $this->text($data, 'notes'),
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);

        $this->entityManager->saveEntity($quote);

        return $quote;
    }

    public function addQuoteItem(string $quoteId, array $data): Entity
    {
        $quote = $this->getInWorkspace('Quote', $quoteId);
        $item = $this->makeItem('QuoteItem', 'quoteId', $quoteId, $data);
        $this->entityManager->saveEntity($item);
        $this->recalculate($quoteId, 'QuoteItem', 'Quote');
        return $item;
    }

    public function convertQuote(string $quoteId): Entity
    {
        $quote = $this->getInWorkspace('Quote', $quoteId);

        if (!in_array($quote->get('status'), ['Accepted', 'Sent', 'Draft'], true)) {
            throw new BadRequest('Quote cannot be converted from its current status.');
        }

        $order = $this->entityManager->getNewEntity('Order');

        $order->set([
            'name' => 'Order from ' . $quote->get('quoteNumber'),
            'orderNumber' => $this->nextNumber('Order', 'orderNumber', 'SO-' . gmdate('Y') . '-'),
            'status' => 'Draft',
            'orderDate' => gmdate('Y-m-d'),
            'currency' => $quote->get('currency') ?: 'INR',
            'subtotal' => $quote->get('subtotal'),
            'taxAmount' => $quote->get('taxAmount'),
            'discountAmount' => $quote->get('discountAmount'),
            'totalAmount' => $quote->get('totalAmount'),
            'notes' => $quote->get('notes'),
            'quoteId' => $quote->getId(),
            'leadId' => $quote->get('leadId'),
            'contactId' => $quote->get('contactId'),
            'accountId' => $quote->get('accountId'),
            'omniGoCRMWorkspaceId' => $this->workspaceId(),
        ]);

        $this->entityManager->saveEntity($order);

        $items = $this->entityManager
            ->getRDBRepository('QuoteItem')
            ->where([
                'quoteId' => $quoteId,
                'deleted' => false,
            ])
            ->find();

        foreach ($items as $source) {
            $target = $this->entityManager->getNewEntity('OrderItem');

            $target->set([
                'name' => $source->get('name'),
                'orderId' => $order->getId(),
                'productId' => $source->get('productId'),
                'description' => $source->get('description'),
                'quantity' => $source->get('quantity'),
                'unitPrice' => $source->get('unitPrice'),
                'discountAmount' => $source->get('discountAmount'),
                'taxAmount' => $source->get('taxAmount'),
                'lineTotal' => $source->get('lineTotal'),
                'currency' => $source->get('currency') ?: $order->get('currency'),
                'omniGoCRMWorkspaceId' => $this->workspaceId(),
            ]);

            $this->entityManager->saveEntity($target);
        }

        $quote->set('status', 'Converted');
        $this->entityManager->saveEntity($quote);

        return $order;
    }

    public function recordPayment(array $data): Entity
    {
        $amount = (float) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new BadRequest('Payment amount must be greater than zero.');
        }

        $orderId = $this->nullableId($data, 'orderId');
        $quoteId = $this->nullableId($data, 'quoteId');
        $leadId = $this->nullableId($data, 'leadId');
        $contactId = $this->nullableId($data, 'contactId');
        $accountId = $this->nullableId($data, 'accountId');

        if ($orderId) { $this->getInWorkspace('Order', $orderId); }
        if ($quoteId) { $this->getInWorkspace('Quote', $quoteId); }

        $payment = $this->entityManager->getNewEntity('Payment');

        $payment->set([
            'name' => $this->text($data, 'name') ?: 'Payment ' . gmdate('Y-m-d H:i:s'),
            'paymentNumber' => $this->nextNumber('Payment', 'paymentNumber', 'PAY-' . gmdate('Y') . '-'),
            'status' => $this->text($data, 'status') ?: 'Paid',
            'paymentDate' => $this->date($data, 'paymentDate') ?: gmdate('Y-m-d'),
            'amount' => $amount,
            'currency' => $this->text($data, 'currency') ?: 'INR',
            'method' => $this->text($data, 'method') ?: 'Other',
            'provider' => $this->text($data, 'provider'),
            'providerPaymentId' => $this->text($data, 'providerPaymentId'),
            'referenceNumber' => $this->text($data, 'referenceNumber'),
            'notes' => $this->text($data, 'notes'),
            'orderId' => $orderId,
            'quoteId' => $quoteId,
            'leadId' => $leadId,
            'contactId' => $contactId,
            'accountId' => $accountId,
            'omniGoCRMWorkspaceId' => $this->workspaceId(),
        ]);

        $this->entityManager->saveEntity($payment);

        return $payment;
    }

    private function recalculate(string $parentId, string $itemEntity, string $headerEntity): void
    {
        $items = $this->entityManager
            ->getRDBRepository($itemEntity)
            ->where([
                $headerEntity === 'Quote' ? 'quoteId' : 'orderId' => $parentId,
                'deleted' => false,
            ])
            ->find();

        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($items as $item) {
            $quantity = max(0, (float) $item->get('quantity'));
            $unitPrice = max(0, (float) $item->get('unitPrice'));
            $discountAmount = max(0, (float) $item->get('discountAmount'));
            $taxAmount = max(0, (float) $item->get('taxAmount'));

            $base = max(0, $quantity * $unitPrice - $discountAmount);
            $lineTotal = $base + $taxAmount;

            $item->set('lineTotal', $lineTotal);
            $this->entityManager->saveEntity($item);

            $subtotal += $quantity * $unitPrice;
            $discount += $discountAmount;
            $tax += $taxAmount;
        }

        $header = $this->getInWorkspace($headerEntity, $parentId);

        $header->set([
            'subtotal' => $subtotal,
            'discountAmount' => $discount,
            'taxAmount' => $tax,
            'totalAmount' => max(0, $subtotal - $discount + $tax),
        ]);

        $this->entityManager->saveEntity($header);
    }

    private function makeItem(string $entityType, string $parentField, string $parentId, array $data): Entity
    {
        $quantity = (float) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unitPrice'] ?? 0);

        if ($quantity <= 0) {
            throw new BadRequest('Quantity must be greater than zero.');
        }

        if ($unitPrice < 0) {
            throw new BadRequest('Unit price cannot be negative.');
        }

        $item = $this->entityManager->getNewEntity($entityType);

        $item->set([
            'name' => $this->text($data, 'name') ?: ($this->text($data, 'description') ?: 'Item'),
            $parentField => $parentId,
            'productId' => $this->nullableId($data, 'productId'),
            'description' => $this->text($data, 'description'),
            'quantity' => $quantity,
            'unitPrice' => $unitPrice,
            'discountAmount' => max(0, (float) ($data['discountAmount'] ?? 0)),
            'taxAmount' => max(0, (float) ($data['taxAmount'] ?? 0)),
            'lineTotal' => 0,
            'currency' => $this->text($data, 'currency') ?: 'INR',
            'omniGoCRMWorkspaceId' => $this->workspaceId(),
        ]);

        return $item;
    }

    private function getInWorkspace(string $entityType, string $id): Entity
    {
        $entity = $this->entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            throw new BadRequest($entityType . ' not found.');
        }

        $workspaceId = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($workspaceId === '' || (string) $entity->get('omniGoCRMWorkspaceId') !== $workspaceId) {
            throw new BadRequest($entityType . ' is outside the active workspace.');
        }

        return $entity;
    }

    private function workspaceId(): string
    {
        $id = trim((string) $this->user->get('omniGoCRMCurrentWorkspaceId'));

        if ($id === '') {
            throw new BadRequest('Select an active OmniGoCRM workspace.');
        }

        return $id;
    }

    private function nextNumber(string $entityType, string $field, string $prefix): string
    {
        $existing = $this->entityManager
            ->getRDBRepository($entityType)
            ->order('createdAt', 'DESC')
            ->findOne();

        $last = 0;

        if ($existing) {
            $value = (string) $existing->get($field);
            if (preg_match('/(\d+)$/', $value, $match)) {
                $last = (int) $match[1];
            }
        }

        return $prefix . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    private function text(array $data, string $key): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
    }

    private function nullableId(array $data, string $key): ?string
    {
        $value = $this->text($data, $key);
        return $value !== '' ? $value : null;
    }

    private function date(array $data, string $key): ?string
    {
        $value = $this->text($data, $key);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('Y-m-d', $timestamp);
    }
}
