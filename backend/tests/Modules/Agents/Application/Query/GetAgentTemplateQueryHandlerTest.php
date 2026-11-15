<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application\Query;

use App\Tests\Fixtures\Agents\InMemoryAgentTemplateRepository;
use DateTimeImmutable;
use Modules\Agents\Application\Query\GetAgentTemplateQuery;
use Modules\Agents\Application\Query\GetAgentTemplateQueryHandler;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Shared\Kernel\AgentTemplateId;
use PHPUnit\Framework\TestCase;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetAgentTemplateQueryHandlerTest extends TestCase
{
    public function testShouldReturnTemplateWhenFoundBySlug(): void
    {
        $repository = new InMemoryAgentTemplateRepository();
        $template = $this->makeTemplate('maja', 'Maja');
        $repository->save($template);

        $sut = new GetAgentTemplateQueryHandler($repository);

        $result = $sut(new GetAgentTemplateQuery(idOrSlug: 'maja'));

        self::assertSame($template, $result);
    }

    public function testShouldReturnTemplateWhenFoundById(): void
    {
        $repository = new InMemoryAgentTemplateRepository();
        $id = AgentTemplateId::generate();
        $template = $this->makeTemplate('maja', 'Maja', $id);
        $repository->save($template);

        $sut = new GetAgentTemplateQueryHandler($repository);

        $result = $sut(new GetAgentTemplateQuery(idOrSlug: (string) $id));

        self::assertSame($template, $result);
    }

    public function testShouldThrowNotFoundWhenSlugUnknown(): void
    {
        $sut = new GetAgentTemplateQueryHandler(new InMemoryAgentTemplateRepository());

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Agent template "missing" not found.');

        $sut(new GetAgentTemplateQuery(idOrSlug: 'missing'));
    }

    public function testShouldThrowNotFoundWhenUuidUnknown(): void
    {
        $sut = new GetAgentTemplateQueryHandler(new InMemoryAgentTemplateRepository());
        $unknown = AgentTemplateId::generate();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(sprintf('Agent template "%s" not found.', (string) $unknown));

        $sut(new GetAgentTemplateQuery(idOrSlug: (string) $unknown));
    }

    private function makeTemplate(string $slug, string $name, ?AgentTemplateId $id = null): AgentTemplate
    {
        return AgentTemplate::create(
            id: $id ?? AgentTemplateId::generate(),
            slug: $slug,
            name: $name,
            role: 'Role',
            roleDescription: 'd',
            expertise: 'e',
            workflow: 'w',
            style: 's',
            outputFormat: 'o',
            now: new DateTimeImmutable('2026-11-15T00:00:00+00:00'),
        );
    }
}
