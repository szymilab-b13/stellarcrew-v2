<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use function base64_encode;
use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function json_encode;

use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\RabbitMqAdminClient;
use Modules\Workers\Domain\RabbitMqAdminException;

use function preg_quote;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function rawurlencode;
use function rtrim;
use function sprintf;

final readonly class HttpRabbitMqAdminClient implements RabbitMqAdminClient
{
    public function __construct(
        private string $baseUrl,
        private string $adminUser,
        private string $adminPassword,
        private string $vhost = '/',
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function provisionWorkerUser(string $username, string $password, WorkerId $workerId): void
    {
        $this->request('PUT', sprintf('/api/users/%s', rawurlencode($username)), [
            'password' => $password,
            'tags' => '',
        ]);

        $workerIdString = (string) $workerId;
        $inbox = sprintf('chat\.worker\.%s\.inbox', preg_quote($workerIdString, '/'));
        $read = sprintf('^(%s|chat\.agent_replies)$', $inbox);
        $write = sprintf('^%s$', $inbox);

        $this->request('PUT', sprintf('/api/permissions/%s/%s', rawurlencode($this->vhost), rawurlencode($username)), [
            'configure' => $read,
            'write' => $write,
            'read' => $read,
        ]);
    }

    public function removeUser(string $username): void
    {
        $this->request('DELETE', sprintf('/api/users/%s', rawurlencode($username)), null, allowNotFound: true);
    }

    /**
     * @param non-empty-string $method
     * @param array<string, string>|null $body
     */
    private function request(string $method, string $path, ?array $body, bool $allowNotFound = false): void
    {
        $url = rtrim($this->baseUrl, '/').$path;
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RabbitMqAdminException(sprintf('Failed to initialise curl for %s %s.', $method, $path));
        }

        $headers = [
            'Accept: application/json',
            sprintf('Authorization: Basic %s', base64_encode(sprintf('%s:%s', $this->adminUser, $this->adminPassword))),
        ];

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

        if ($body !== null) {
            $payload = json_encode($body, JSON_THROW_ON_ERROR);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($handle, CURLOPT_HTTPHEADER, [...$headers, 'Content-Type: application/json']);
        }

        $response = curl_exec($handle);
        if ($response === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            curl_close($handle);

            throw new RabbitMqAdminException(sprintf('curl error (%d) calling %s %s: %s', $errno, $method, $path, $error));
        }

        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($status >= 200 && $status < 300) {
            $this->logger->debug('rabbitmq admin request succeeded', ['method' => $method, 'path' => $path, 'status' => $status]);

            return;
        }

        if ($status === 404 && $allowNotFound) {
            $this->logger->debug('rabbitmq admin request returned 404 (allowed)', ['method' => $method, 'path' => $path]);

            return;
        }

        throw new RabbitMqAdminException(sprintf('RabbitMQ admin %s %s responded %d: %s', $method, $path, $status, (string) $response));
    }
}
