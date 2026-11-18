<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Messenger;

use InvalidArgumentException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use JsonException;
use LogicException;
use Modules\Chat\Application\AgentReplyRequested;

use function sprintf;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Encodes AgentReplyRequested as JSON so the Node worker daemon can parse
 * the AMQP payload directly. Backend never consumes this transport.
 */
final class AgentReplyRequestedSerializer implements SerializerInterface
{
    private const string CONTENT_TYPE = 'application/json';

    private const string MESSAGE_TYPE = 'AgentReplyRequested';

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        if (!$message instanceof AgentReplyRequested) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s message, got %s.',
                AgentReplyRequested::class,
                $message::class,
            ));
        }

        try {
            $body = json_encode([
                'threadId' => (string) $message->threadId,
                'workerId' => (string) $message->workerId,
                'agentId' => (string) $message->agentId,
                'userMessageId' => (string) $message->userMessageId,
                'userContent' => $message->userContent,
                'subject' => $message->subject,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $jsonException) {
            throw new LogicException('Failed to encode AgentReplyRequested payload.', 0, $jsonException);
        }

        return [
            'body' => $body,
            'headers' => [
                'Content-Type' => self::CONTENT_TYPE,
                'type' => self::MESSAGE_TYPE,
            ],
        ];
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        throw new MessageDecodingFailedException(sprintf(
            '%s is produced by Symfony but consumed by the Node worker daemon; backend cannot decode it.',
            AgentReplyRequested::class,
        ));
    }
}
