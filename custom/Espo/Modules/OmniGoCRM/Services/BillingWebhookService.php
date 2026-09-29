<?php

namespace Espo\Modules\OmniGoCRM\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;

class BillingWebhookService
{
    public function __construct(private Config $config, private EntityManager $entityManager) {}

    public function handle(string $provider, string $rawBody, ?string $signature): array
    {
        $provider = ucfirst(strtolower($provider));
        if ($provider === 'Stripe') {
            $this->verifyStripe($rawBody, $signature);
        } elseif ($provider === 'Razorpay') {
            $this->verifyRazorpay($rawBody, $signature);
        } else {
            throw new Forbidden('Unsupported billing provider.');
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            throw new Forbidden('Invalid billing webhook JSON.');
        }

        $eventType = (string) ($payload['type'] ?? $payload['event'] ?? '');
        if (!$this->isSubscriptionEvent($provider, $eventType)) {
            return ['accepted' => true, 'updated' => false, 'reason' => 'event type is not a subscription lifecycle event'];
        }

        $object = $provider === 'Stripe'
            ? ($payload['data']['object'] ?? [])
            : ($payload['payload']['subscription']['entity'] ?? []);
        if (!is_array($object)) {
            return ['accepted' => true, 'updated' => false, 'reason' => 'subscription object missing'];
        }

        if ($provider === 'Stripe' && ($object['object'] ?? '') !== 'subscription') {
            return ['accepted' => true, 'updated' => false, 'reason' => 'subscription object missing'];
        }

        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $notes = is_array($object['notes'] ?? null) ? $object['notes'] : [];
        $workspaceId = trim((string) ($metadata['workspaceId'] ?? $notes['workspaceId'] ?? ''));
        $subscriptionId = (string) ($object['id'] ?? '');
        if ($workspaceId === '' || $subscriptionId === '') {
            return ['accepted' => true, 'updated' => false, 'reason' => 'workspaceId or subscription id missing'];
        }

        $externalEventId = $provider . ':' . (string) ($payload['id'] ?? hash('sha256', $rawBody));
        $event = $this->entityManager->getRDBRepository('BillingEvent')->where([
            'externalEventId' => $externalEventId,
            'deleted' => false,
        ])->findOne();
        if ($event && $event->get('status') === 'Processed') {
            return ['accepted' => true, 'updated' => false, 'duplicate' => true];
        }

        $status = $this->mapStatus((string) ($object['status'] ?? ''));
        $plan = (string) ($metadata['plan'] ?? $notes['plan'] ?? '');

        $subscriptionRepository = $this->entityManager->getRDBRepository('BillingSubscription');
        $subscriptionForProviderId = $subscriptionRepository->where([
            'provider' => $provider,
            'providerSubscriptionId' => $subscriptionId,
            'deleted' => false,
        ])->findOne();
        if ($subscriptionForProviderId && (string) $subscriptionForProviderId->get('workspaceId') !== $workspaceId) {
            throw new Forbidden('The billing subscription is already assigned to another workspace.');
        }

        $subscription = $subscriptionRepository->where([
            'workspaceId' => $workspaceId,
            'deleted' => false,
        ])->findOne();

        $isCreationEvent = ($provider === 'Stripe' && $eventType === 'customer.subscription.created') ||
            ($provider === 'Razorpay' && $eventType === 'subscription.activated');
        if (
            $subscription &&
            (string) $subscription->get('provider') === $provider &&
            trim((string) $subscription->get('providerSubscriptionId')) !== '' &&
            (string) $subscription->get('providerSubscriptionId') !== $subscriptionId &&
            $subscription->get('status') === 'Active' &&
            !$isCreationEvent
        ) {
            return ['accepted' => true, 'updated' => false, 'reason' => 'event is for a superseded subscription'];
        }

        if (
            $plan === '' &&
            $subscription &&
            (string) $subscription->get('provider') === $provider &&
            (string) $subscription->get('providerSubscriptionId') === $subscriptionId
        ) {
            $plan = (string) $subscription->get('plan');
        }
        if (!in_array($plan, ['Free', 'Starter', 'Business', 'Enterprise'], true)) {
            return ['accepted' => true, 'updated' => false, 'reason' => 'subscription plan is missing or invalid'];
        }

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
        if (!$workspace) {
            return ['accepted' => true, 'updated' => false, 'reason' => 'workspace not found'];
        }

        if (!$subscription) $subscription = $this->entityManager->getNewEntity('BillingSubscription');
        $amount = isset($object['amount'])
            ? (float) $object['amount'] / 100
            : $subscription->get('amount');
        $currency = (string) ($object['currency'] ?? $subscription->get('currency') ?? 'INR');

        $subscription->setMultiple([
            'name' => 'Billing Subscription',
            'workspaceId' => $workspaceId,
            'provider' => $provider,
            'providerSubscriptionId' => $subscriptionId,
            'providerCustomerId' => (string) ($object['customer_id'] ?? $object['customer'] ?? ''),
            'plan' => $plan,
            'status' => $status,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'metadataJson' => json_encode([
                'eventId' => $payload['id'] ?? null,
                'eventType' => $eventType,
                'subscriptionId' => $subscriptionId,
                'plan' => $plan,
                'status' => $status,
            ], JSON_UNESCAPED_SLASHES),
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
        $this->entityManager->saveEntity($subscription);

        $workspace->setMultiple(['plan' => $plan, 'subscriptionStatus' => $status]);
        $this->entityManager->saveEntity($workspace);

        if (!$event) $event = $this->entityManager->getNewEntity('BillingEvent');
        $event->setMultiple([
            'name' => $eventType,
            'provider' => $provider,
            'externalEventId' => $externalEventId,
            'eventType' => $eventType,
            'status' => 'Processed',
            'payloadJson' => json_encode([
                'subscriptionId' => $subscriptionId,
                'plan' => $plan,
                'status' => $status,
            ], JSON_UNESCAPED_SLASHES),
            'processedAt' => gmdate('Y-m-d H:i:s'),
            'workspaceId' => $workspaceId,
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
        $this->entityManager->saveEntity($event);

        return ['accepted' => true, 'updated' => true, 'workspaceId' => $workspaceId, 'plan' => $plan, 'status' => $status];
    }

    private function isSubscriptionEvent(string $provider, string $eventType): bool
    {
        if ($provider === 'Stripe') {
            return in_array($eventType, [
                'customer.subscription.created',
                'customer.subscription.updated',
                'customer.subscription.deleted',
                'customer.subscription.paused',
                'customer.subscription.resumed',
            ], true);
        }

        return in_array($eventType, [
            'subscription.activated',
            'subscription.pending',
            'subscription.halted',
            'subscription.cancelled',
            'subscription.paused',
            'subscription.resumed',
            'subscription.completed',
            'subscription.charged',
        ], true);
    }

    private function verifyStripe(string $body, ?string $signature): void
    {
        $secret = trim((string) $this->config->get('omniGoCRMStripeWebhookSecret'));
        if ($secret === '' || !is_string($signature)) throw new Forbidden('Stripe webhook secret/signature missing.');
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key][] = $value;
        }
        $timestamp = (int) ($parts['t'][0] ?? 0);
        $signed = $timestamp . '.' . $body;
        $expected = hash_hmac('sha256', $signed, $secret);
        $valid = false;
        foreach ($parts['v1'] ?? [] as $candidate) $valid = $valid || hash_equals($expected, $candidate);
        if (!$valid || abs(time() - $timestamp) > 300) throw new Forbidden('Invalid Stripe webhook signature.');
    }

    private function verifyRazorpay(string $body, ?string $signature): void
    {
        $secret = trim((string) $this->config->get('omniGoCRMRazorpayWebhookSecret'));
        if ($secret === '' || !is_string($signature) || !hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
            throw new Forbidden('Invalid Razorpay webhook signature.');
        }
    }

    private function mapStatus(string $status): string
    {
        $s = strtolower($status);
        return match (true) {
            in_array($s, ['active', 'activated'], true) => 'Active',
            in_array($s, ['trial', 'trialing'], true) => 'Trialing',
            in_array($s, ['past_due', 'unpaid', 'pending', 'halted', 'paused'], true) => 'Past Due',
            in_array($s, ['canceled', 'cancelled', 'completed'], true) => 'Canceled',
            in_array($s, ['incomplete', 'incomplete_expired'], true) => 'Incomplete',
            default => 'Incomplete',
        };
    }
}
