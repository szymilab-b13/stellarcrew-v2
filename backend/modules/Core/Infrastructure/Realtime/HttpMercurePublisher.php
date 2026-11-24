<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Realtime;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use JsonException;
use LogicException;
use Modules\Core\Api\RealtimePublisher;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publishes events to a Mercure hub via HTTP POST. Failures are logged and
 * swallowed: realtime is a best-effort channel and must not block the
 * command that triggered the publication.
 */
final readonly class HttpMercurePublisher implements RealtimePublisher
{
    public function __construct(
        private HttpClientInterface $http,
        private MercureJwtFactory $jwtFactory,
        private LoggerInterface $logger,
        private string $hubUrl,
    ) {
    }

    public function publish(string $topic, array $data): void
    {
        try {
            $payload = json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException $jsonException) {
            throw new LogicException('Realtime payload could not be JSON-encoded.', 0, $jsonException);
        }

        $token = $this->jwtFactory->issue([$topic], []);

        try {
            $response = $this->http->request('POST', $this->hubUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'topic' => $topic,
                    'data' => $payload,
                ],
            ]);

            $status = $response->getStatusCode();
            if ($status >= 300) {
                throw new RuntimeException('Mercure hub rejected publish with HTTP ' . $status);
            }
        } catch (TransportExceptionInterface | RuntimeException $exception) {
            $this->logger->warning('Mercure publish failed', [
                'topic' => $topic,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
