<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application\Query;

use App\Tests\Fixtures\Agents\InMemoryAgentTemplateRepository;
use DateTimeImmutable;
use Modules\Agents\Application\Query\ListAgentTemplatesQueryHandler;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Shared\Kernel\AgentTemplateId;
use PHPUnit\Framework\TestCase;

final class ListAgentTemplatesQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyWhenNoTemplatesSeeded(): void
    {
        $sut = new ListAgentTemplatesQueryHandler(new InMemoryAgentTemplateRepository());

        $result = $sut();

        self::assertSame([], $result);
    }

    public function testShouldReturnAllTemplatesOrderedByName(): void
    {
        $repository = new InMemoryAgentTemplateRepository();
        $now = new DateTimeImmutable('2026-11-15T00:00:00+00:00');

        $repository->save($this->makeTemplate('viktor', 'Viktor', 'Context Engineer', $now));
        $repository->save($this->makeTemplate('maja', 'Maja', 'Project Manager', $now));
        $repository->save($this->makeTemplate('hefajstos', 'Hefajstos', 'Autonomous Builder', $now));

        $sut = new ListAgentTemplatesQueryHandler($repository);

        $result = $sut();

        self::assertCount(3, $result);
        self::assertSame('Hefajstos', $result[0]->name());
        self::assertSame('Maja', $result[1]->name());
        self::assertSame('Viktor', $result[2]->name());
    }

    private function makeTemplate(string $slug, string $name, string $role, DateTimeImmutable $now): AgentTemplate
    {
        return AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: $slug,
            name: $name,
            role: $role,
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: $now,
        );
    }
}
