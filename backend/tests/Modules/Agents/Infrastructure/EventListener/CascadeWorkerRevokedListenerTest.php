<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Infrastructure\EventListener;

use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Domain\Agent;
use Modules\Agents\Infrastructure\EventListener\CascadeWorkerRevokedListener;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Api\Event\WorkerRevoked;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CascadeWorkerRevokedListenerTest extends TestCase
{
    public function testShouldClearDefaultWorkerOnAllMatchingAgents(): void
    {
        $agents = new InMemoryAgentRepository();
        $workerId = WorkerId::generate();
        $otherWorkerId = WorkerId::generate();
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $matching1 = $this->seedAgent($createdAt, 'agent-one');
        $matching1->assignDefaultWorker($workerId, $createdAt);

        $agents->save($matching1);
        $matching2 = $this->seedAgent($createdAt, 'agent-two');
        $matching2->assignDefaultWorker($workerId, $createdAt);

        $agents->save($matching2);
        $otherAssigned = $this->seedAgent($createdAt, 'agent-three');
        $otherAssigned->assignDefaultWorker($otherWorkerId, $createdAt);

        $agents->save($otherAssigned);
        $withoutWorker = $this->seedAgent($createdAt, 'agent-four');
        $agents->save($withoutWorker);
        $now = new DateTimeImmutable('2026-11-18T12:00:00+00:00');
        $sut = new CascadeWorkerRevokedListener($agents);

        $sut(new WorkerRevoked($workerId, new UserId(Uuid::v7()), new UserId(Uuid::v7()), $now));

        self::assertNull($agents->findById($matching1->id())?->defaultWorkerId());
        self::assertNull($agents->findById($matching2->id())?->defaultWorkerId());
        self::assertTrue($agents->findById($otherAssigned->id())?->defaultWorkerId()?->equals($otherWorkerId));
        self::assertNull($agents->findById($withoutWorker->id())?->defaultWorkerId());
    }

    public function testShouldDoNothingWhenNoAgentReferencesWorker(): void
    {
        $agents = new InMemoryAgentRepository();
        $createdAt = new DateTimeImmutable('2026-11-18T10:00:00+00:00');
        $unrelated = $this->seedAgent($createdAt, 'unrelated');
        $agents->save($unrelated);
        $sut = new CascadeWorkerRevokedListener($agents);

        $sut(new WorkerRevoked(
            WorkerId::generate(),
            new UserId(Uuid::v7()),
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-18T12:00:00+00:00'),
        ));

        self::assertNull($agents->findById($unrelated->id())?->defaultWorkerId());
    }

    private function seedAgent(DateTimeImmutable $now, string $slug): Agent
    {
        return Agent::create(
            new AgentId(Uuid::v7()),
            new OrganizationId(Uuid::v7()),
            new UserId(Uuid::v7()),
            $slug,
            'Name',
            'Role',
            'Description',
            '- Expertise',
            '- Workflow',
            '- Style',
            '- Output',
            $now,
        );
    }
}
