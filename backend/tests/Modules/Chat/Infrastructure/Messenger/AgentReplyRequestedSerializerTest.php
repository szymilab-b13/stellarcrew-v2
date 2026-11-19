<?php

declare(strict_types=1);

namespace Tests\Modules\Chat\Infrastructure\Messenger;

use InvalidArgumentException;

use const JSON_THROW_ON_ERROR;

use Modules\Chat\Application\AgentReplyRequested;
use Modules\Chat\Infrastructure\Messenger\AgentReplyRequestedSerializer;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Uid\Uuid;

final class AgentReplyRequestedSerializerTest extends TestCase
{
    private AgentReplyRequestedSerializer $sut;

    protected function setUp(): void
    {
        $this->sut = new AgentReplyRequestedSerializer();
    }

    public function testShouldEncodeAgentReplyRequestedAsJsonBody(): void
    {
        $threadId = ChatThreadId::fromString('01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0');
        $workerId = WorkerId::fromString('01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00');
        $agentId = Uuid::fromString('01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10');
        $userMessageId = ChatMessageId::fromString('01976b7a-ba12-7a6b-9f3b-40ab82c9d100');
        $envelope = new Envelope(new AgentReplyRequested(
            threadId: $threadId,
            workerId: $workerId,
            agentId: $agentId,
            userMessageId: $userMessageId,
            userContent: 'Please draft the release notes.',
            subject: 'Release notes draft',
        ));

        $encoded = $this->sut->encode($envelope);

        self::assertArrayHasKey('body', $encoded);
        self::assertArrayHasKey('headers', $encoded);
        self::assertSame('application/json', $encoded['headers']['Content-Type']);
        self::assertSame('AgentReplyRequested', $encoded['headers']['type']);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($encoded['body'], associative: true, flags: JSON_THROW_ON_ERROR);
        self::assertSame((string) $threadId, $payload['threadId']);
        self::assertSame((string) $workerId, $payload['workerId']);
        self::assertSame((string) $agentId, $payload['agentId']);
        self::assertSame((string) $userMessageId, $payload['userMessageId']);
        self::assertSame('Please draft the release notes.', $payload['userContent']);
        self::assertSame('Release notes draft', $payload['subject']);
    }

    public function testShouldPreserveUtf8ContentWithoutEscapeSlashes(): void
    {
        $envelope = new Envelope(new AgentReplyRequested(
            threadId: ChatThreadId::fromString('01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0'),
            workerId: WorkerId::fromString('01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00'),
            agentId: Uuid::fromString('01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10'),
            userMessageId: ChatMessageId::fromString('01976b7a-ba12-7a6b-9f3b-40ab82c9d100'),
            userContent: 'Zażółć gęślą jaźń — https://example.com/żółw',
            subject: 'łóżka',
        ));

        $encoded = $this->sut->encode($envelope);

        self::assertStringContainsString('Zażółć', $encoded['body']);
        self::assertStringContainsString('https://example.com/żółw', $encoded['body']);
        self::assertStringNotContainsString('\\/', $encoded['body']);
    }

    public function testShouldRejectEnvelopeCarryingDifferentMessage(): void
    {
        $envelope = new Envelope(new stdClass());

        $this->expectException(InvalidArgumentException::class);

        $this->sut->encode($envelope);
    }

    public function testShouldThrowMessageDecodingFailedBecauseWorkerConsumes(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        $this->sut->decode(['body' => '{}']);
    }
}
