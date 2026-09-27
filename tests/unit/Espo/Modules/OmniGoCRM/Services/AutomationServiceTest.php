<?php

namespace tests\unit\Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Modules\OmniGoCRM\Services\AutomationService;
use Espo\Modules\OmniGoCRM\Services\WhatsAppCloudApi;
use Espo\Modules\OmniGoCRM\Services\WhatsAppConversationService;
use Espo\ORM\Entity;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AutomationServiceTest extends TestCase
{
    public function testGroupedConditionsAreCaseInsensitive(): void
    {
        $record = $this->createMock(Entity::class);
        $record->method('get')->willReturnCallback(
            fn (string $field): mixed => [
                'textBody' => 'Hello from WhatsApp',
                'messageType' => 'Text',
                'direction' => 'Inbound',
            ][$field] ?? null,
        );
        $service = $this->serviceWithRecord($record);

        $matches = $this->invoke($service, 'conditionsMatch', [[
            'all' => [
                ['field' => 'messageType', 'value' => 'text'],
                ['field' => 'textBody', 'operator' => 'contains', 'value' => 'WHATSAPP'],
            ],
            'any' => [
                ['field' => 'direction', 'value' => 'outbound'],
                ['field' => 'textBody', 'operator' => 'startsWith', 'value' => 'hello'],
            ],
        ], 'WhatsAppMessage', 'message-1']);

        self::assertTrue($matches);
    }

    public function testAnyGroupMustMatchWhenPresent(): void
    {
        $record = $this->createMock(Entity::class);
        $record->method('get')->willReturnCallback(
            fn (string $field): mixed => ['textBody' => 'A customer message'][$field] ?? null,
        );
        $service = $this->serviceWithRecord($record);

        $matches = $this->invoke($service, 'conditionsMatch', [[
            'all' => [['field' => 'textBody', 'operator' => 'contains', 'value' => 'customer']],
            'any' => [['field' => 'textBody', 'operator' => 'equals', 'value' => 'not this']],
        ], 'WhatsAppMessage', 'message-1']);

        self::assertFalse($matches);
    }

    public function testWaitDurationIsBounded(): void
    {
        $service = $this->serviceWithRecord($this->createMock(Entity::class));

        self::assertSame(172800, $this->invoke($service, 'getWaitSeconds', [['duration' => 2, 'unit' => 'days']]));
        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);
        $this->invoke($service, 'getWaitSeconds', [['duration' => 31, 'unit' => 'days']]);
    }

    public function testWhatsAppTextWindowExpiresAtExactly24Hours(): void
    {
        $service = $this->serviceWithRecord($this->createMock(Entity::class));
        $receivedAt = '2025-01-01 00:00:00';
        $lastSecond = strtotime('2025-01-01 23:59:59 UTC');
        $expired = strtotime('2025-01-02 00:00:00 UTC');

        self::assertTrue($this->invoke($service, 'isWithinWhatsAppTextWindow', [$receivedAt, $lastSecond]));
        self::assertFalse($this->invoke($service, 'isWithinWhatsAppTextWindow', [$receivedAt, $expired]));
        self::assertFalse($this->invoke($service, 'isWithinWhatsAppTextWindow', ['not-a-date', $lastSecond]));
    }

    public function testWorkspaceGuardRejectsCrossWorkspaceEntity(): void
    {
        $record = $this->createMock(Entity::class);
        $record->method('get')->willReturnCallback(
            fn (string $field): mixed => ['omniGoCRMWorkspaceId' => 'workspace-a'][$field] ?? null,
        );
        $service = $this->serviceWithRecord($record);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);
        $this->invoke($service, 'assertEntityWorkspace', [$record, 'workspace-b', 'WhatsApp recipient']);
    }

    private function serviceWithRecord(Entity $record): AutomationService
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getEntityById')
            ->willReturnMap([['WhatsAppMessage', 'message-1', $record]]);

        return new AutomationService(
            $entityManager,
            $this->createMock(WhatsAppCloudApi::class),
            $this->createMock(WhatsAppConversationService::class),
        );
    }

    private function invoke(AutomationService $service, string $method, array $arguments): mixed
    {
        return (new ReflectionMethod($service, $method))->invokeArgs($service, $arguments);
    }
}
