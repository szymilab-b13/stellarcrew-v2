<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Infrastructure\Http;

use App\Tests\Fixtures\Agents\InMemoryAgentTemplateRepository;
use DateTimeImmutable;
use Modules\Agents\Domain\AgentTemplate;
use Modules\Agents\Domain\AgentTemplateRepository;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\HashablePlaintextUser;
use Modules\Shared\Kernel\AgentTemplateId;
use Modules\Shared\Kernel\UserId;

use function sprintf;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AgentTemplatesEndpointTest extends WebTestCase
{
    private const string DEFAULT_EMAIL = 'templates-reader@example.com';

    private const string DEFAULT_PASSWORD = 'secret-pass-long-enough';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    public function testShouldReturn401WhenTokenMissingOnList(): void
    {
        $this->getJson('/api/agent-templates', null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200WithEmptyListWhenNoTemplatesSeeded(): void
    {
        $token = $this->loginUser();

        $this->getJson('/api/agent-templates', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertSame(['items' => []], $this->decode());
    }

    public function testShouldReturn200WithSortedListWhenTemplatesSeeded(): void
    {
        $token = $this->loginUser();
        $this->seedTemplate('viktor', 'Viktor', 'Context Engineer');
        $this->seedTemplate('maja', 'Maja', 'Project Manager');
        $this->seedTemplate('hefajstos', 'Hefajstos', 'Autonomous Builder');

        $this->getJson('/api/agent-templates', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $items = $this->decode()['items'];
        self::assertCount(3, $items);
        self::assertSame('Hefajstos', $items[0]['name']);
        self::assertSame('Maja', $items[1]['name']);
        self::assertSame('Viktor', $items[2]['name']);
        self::assertSame('maja', $items[1]['slug']);
        self::assertSame('Project Manager', $items[1]['role']);
    }

    public function testShouldReturn401WhenTokenMissingOnGet(): void
    {
        $this->getJson('/api/agent-templates/maja', null);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn404WhenSlugMissing(): void
    {
        $token = $this->loginUser();

        $this->getJson('/api/agent-templates/missing-slug', $token);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testShouldReturn200WithTemplatePayloadWhenFoundBySlug(): void
    {
        $token = $this->loginUser();
        $template = $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->getJson('/api/agent-templates/maja', $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame((string) $template->id(), $payload['id']);
        self::assertSame('maja', $payload['slug']);
        self::assertSame('Maja', $payload['name']);
        self::assertSame('Project Manager', $payload['role']);
        self::assertArrayHasKey('roleDescription', $payload);
        self::assertArrayHasKey('expertise', $payload);
        self::assertArrayHasKey('workflow', $payload);
        self::assertArrayHasKey('style', $payload);
        self::assertArrayHasKey('outputFormat', $payload);
        self::assertArrayHasKey('createdAt', $payload);
        self::assertArrayHasKey('updatedAt', $payload);
    }

    public function testShouldReturn200WithTemplatePayloadWhenFoundById(): void
    {
        $token = $this->loginUser();
        $template = $this->seedTemplate('maja', 'Maja', 'Project Manager');

        $this->getJson(sprintf('/api/agent-templates/%s', (string) $template->id()), $token);

        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $payload = $this->decode();
        self::assertSame((string) $template->id(), $payload['id']);
        self::assertSame('maja', $payload['slug']);
    }

    private function loginUser(): string
    {
        $this->seedUser(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);

        return $this->login(self::DEFAULT_EMAIL, self::DEFAULT_PASSWORD);
    }

    private function seedUser(string $email, string $plaintextPassword): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $passwordHash = $hasher->hashPassword(new HashablePlaintextUser(), $plaintextPassword);

        $user = User::createAdmin(
            new UserId(Uuid::v7()),
            $email,
            $passwordHash,
            new DateTimeImmutable('2026-11-15T10:00:00+00:00'),
        );

        /** @var UserRepository $users */
        $users = self::getContainer()->get(UserRepository::class);
        $users->save($user);
    }

    private function seedTemplate(string $slug, string $name, string $role): AgentTemplate
    {
        $template = AgentTemplate::create(
            id: AgentTemplateId::generate(),
            slug: $slug,
            name: $name,
            role: $role,
            roleDescription: sprintf('You are %s.', $name),
            expertise: '- Expertise bullet.',
            workflow: '- Workflow bullet.',
            style: '- Style bullet.',
            outputFormat: '- Output format bullet.',
            now: new DateTimeImmutable('2026-11-15T00:00:00+00:00'),
        );

        /** @var InMemoryAgentTemplateRepository $repository */
        $repository = self::getContainer()->get(AgentTemplateRepository::class);
        $repository->save($template);

        return $template;
    }

    private function login(string $email, string $plaintextPassword): string
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        $this->client->request('POST', '/api/auth/login', server: $server, content: json_encode([
            'email' => $email,
            'password' => $plaintextPassword,
        ], JSON_THROW_ON_ERROR));

        $payload = $this->decode();

        return (string) $payload['token'];
    }

    private function getJson(string $path, ?string $token): void
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request('GET', $path, server: $server);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(): array
    {
        $content = (string) $this->client->getResponse()->getContent();
        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
