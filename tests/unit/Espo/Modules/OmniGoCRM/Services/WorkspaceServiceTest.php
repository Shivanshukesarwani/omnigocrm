<?php

namespace tests\unit\Espo\Modules\OmniGoCRM\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Modules\OmniGoCRM\Services\WorkspaceService;
use Espo\ORM\Entity;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Repository\RDBSelectBuilder;
use PHPUnit\Framework\TestCase;

class WorkspaceServiceTest extends TestCase
{
    public function testUniqueSlugNormalizesAndAddsSuffixes(): void
    {
        $candidate = '';
        $existing = $this->createMock(Entity::class);
        $builder = $this->createMock(RDBSelectBuilder::class);
        $builder->method('findOne')->willReturnCallback(static function () use (&$candidate, $existing): ?Entity {
            return in_array($candidate, ['sales-team', 'sales-team-2'], true)
                ? $existing
                : null;
        });

        $repository = $this->createMock(RDBRepository::class);
        $repository->method('where')->willReturnCallback(
            static function (array $where) use (&$candidate, $builder): RDBSelectBuilder {
                $candidate = $where['slug'];

                return $builder;
            }
        );

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRDBRepository')->with('Workspace')->willReturn($repository);

        $service = new WorkspaceService($entityManager, $this->createMock(User::class));

        self::assertSame('sales-team-3', $service->uniqueSlug(' Sales Team '));
        self::assertSame('workspace', $service->uniqueSlug('---'));
    }

    public function testUniqueSlugStaysWithinTheEntityLimit(): void
    {
        $candidate = '';
        $existing = $this->createMock(Entity::class);
        $builder = $this->createMock(RDBSelectBuilder::class);
        $builder->method('findOne')->willReturnCallback(static function () use (&$candidate, $existing): ?Entity {
            return $candidate === str_repeat('x', 100) ? $existing : null;
        });

        $repository = $this->createMock(RDBRepository::class);
        $repository->method('where')->willReturnCallback(
            static function (array $where) use (&$candidate, $builder): RDBSelectBuilder {
                $candidate = $where['slug'];

                return $builder;
            }
        );

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->method('getRDBRepository')->with('Workspace')->willReturn($repository);

        $service = new WorkspaceService($entityManager, $this->createMock(User::class));
        $slug = $service->uniqueSlug(str_repeat('x', 120));

        self::assertSame(100, strlen($slug));
        self::assertStringEndsWith('-2', $slug);
    }
}
