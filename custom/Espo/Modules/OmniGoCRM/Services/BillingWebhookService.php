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

        $object = $payload['data']['object'] ?? $payload['payload']['subscription']['entity'] ?? [];
        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $workspaceId = (string) ($metadata['workspaceId'] ?? '');
        $subscriptionId = (string) ($object['id'] ?? '');
        if ($workspaceId === '' || $subscriptionId === '') {
            return ['accepted' => true, 'updated' => false, 'reason' => 'workspaceId or subscription id missing'];
        }

        $status = $this->mapStatus((string) ($object['status'] ?? ($payload['event'] ?? '')));
        $plan = (string) ($metadata['plan'] ?? 'Free');
        if (!in_array($plan, ['Free','Starter','Business','Enterprise'], true)) $plan = 'Free';

        $subscription = $this->entityManager->getRDBRepository('BillingSubscription')->where([
            'workspaceId' => $workspaceId,
            'deleted' => false,
        ])->findOne();
        if (!$subscription) $subscription = $this->entityManager->getNewEntity('BillingSubscription');

        $subscription->set([
            'name' => 'Billing Subscription',
            'workspaceId' => $workspaceId,
            'provider' => $provider,
            'providerSubscriptionId' => $subscriptionId,
            'providerCustomerId' => (string) ($object['customer_id'] ?? $object['customer'] ?? ''),
            'plan' => $plan,
            'status' => $status,
            'amount' => (float) ($object['amount'] ?? 0) / 100,
            'currency' => strtoupper((string) ($object['currency'] ?? 'INR')),
            'metadataJson' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            'omniGoCRMWorkspaceId' => $workspaceId,
        ]);
        $this->entityManager->saveEntity($subscription);

        $workspace = $this->entityManager->getEntityById('Workspace', $workspaceId);
        if ($workspace) {
            $workspace->set(['plan' => $plan, 'subscriptionStatus' => $status]);
            $this->entityManager->saveEntity($workspace);
        }

        return ['accepted' => true, 'updated' => true, 'workspaceId' => $workspaceId, 'plan' => $plan, 'status' => $status];
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
            str_contains($s, 'active'), str_contains($s, 'paid'), str_contains($s, 'complete') => 'Active',
            str_contains($s, 'past_due'), str_contains($s, 'unpaid') => 'Past Due',
            str_contains($s, 'cancel') => 'Canceled',
            str_contains($s, 'trial') => 'Trialing',
            default => 'Incomplete',
        };
    }
}
