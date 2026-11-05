<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use function array_slice;

use Modules\Core\Application\Install\InvalidInstallInputException;
use Modules\Core\Application\Install\SystemAlreadyInstalledException;

use function sprintf;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ExceptionListener
{
    private const int MAX_TRACE_FRAMES = 10;

    public function __construct(
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        [$status, $code, $details] = $this->resolve($throwable);
        $message = $this->resolveMessage($throwable, $status);

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, mixed>|list<array<string, string>>|null}
     */
    private function resolve(Throwable $throwable): array
    {
        if ($throwable instanceof ValidationFailedException) {
            return [Response::HTTP_UNPROCESSABLE_ENTITY, 'VALIDATION_ERROR', $this->mapViolations($throwable)];
        }

        if ($throwable instanceof InvalidInstallInputException) {
            return [Response::HTTP_BAD_REQUEST, 'INVALID_INPUT', null];
        }

        if ($throwable instanceof SystemAlreadyInstalledException) {
            return [Response::HTTP_NOT_FOUND, 'NOT_FOUND', null];
        }

        if ($throwable instanceof AuthenticationException) {
            return [Response::HTTP_UNAUTHORIZED, 'UNAUTHORIZED', null];
        }

        if ($throwable instanceof AccessDeniedException) {
            return [Response::HTTP_FORBIDDEN, 'FORBIDDEN', null];
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $code = match ($status) {
                Response::HTTP_NOT_FOUND => 'NOT_FOUND',
                Response::HTTP_FORBIDDEN => 'FORBIDDEN',
                Response::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
                default => sprintf('HTTP_%d', $status),
            };

            return [$status, $code, null];
        }

        if ($this->debug) {
            return [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'INTERNAL_ERROR',
                [
                    'exception' => $throwable::class,
                    'trace' => $this->topTrace($throwable),
                ],
            ];
        }

        return [Response::HTTP_INTERNAL_SERVER_ERROR, 'INTERNAL_ERROR', null];
    }

    private function resolveMessage(Throwable $throwable, int $status): string
    {
        if ($throwable instanceof ValidationFailedException) {
            return 'Validation failed';
        }

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR && !$this->debug) {
            return 'Internal server error';
        }

        return $throwable->getMessage();
    }

    /**
     * @return list<array<string, string>>
     */
    private function mapViolations(ValidationFailedException $exception): array
    {
        $details = [];
        foreach ($exception->getViolations() as $violation) {
            /** @var ConstraintViolationInterface $violation */
            $details[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $details;
    }

    /**
     * @return list<array<string, string|int>>
     */
    private function topTrace(Throwable $throwable): array
    {
        $trace = [];
        foreach (array_slice($throwable->getTrace(), 0, self::MAX_TRACE_FRAMES) as $frame) {
            $trace[] = [
                'file' => isset($frame['file']) ? (string) $frame['file'] : '',
                'line' => isset($frame['line']) ? (int) $frame['line'] : 0,
                'function' => isset($frame['function']) ? (string) $frame['function'] : '',
            ];
        }

        return $trace;
    }
}
