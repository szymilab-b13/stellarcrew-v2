<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Http;

use function count;

use Modules\Core\Infrastructure\Http\ExceptionListener;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ExceptionListenerTest extends TestCase
{
    public function testShouldReturn422JsonWhenValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Not a valid email', null, [], null, 'email', 'bad'),
            new ConstraintViolation('Too short', null, [], null, 'name', 'x'),
        ]);
        $event = $this->makeEvent(new ValidationFailedException('payload', $violations));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('VALIDATION_ERROR', $payload['error']['code']);
        self::assertSame('Validation failed', $payload['error']['message']);
        self::assertSame(
            [
                ['field' => 'email', 'message' => 'Not a valid email'],
                ['field' => 'name', 'message' => 'Too short'],
            ],
            $payload['error']['details'],
        );
    }

    public function testShouldReturn404JsonWhenNotFoundHttpException(): void
    {
        $event = $this->makeEvent(new NotFoundHttpException('Project missing'));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('NOT_FOUND', $payload['error']['code']);
        self::assertSame('Project missing', $payload['error']['message']);
        self::assertArrayNotHasKey('details', $payload['error']);
    }

    public function testShouldReturn403JsonWhenAccessDeniedException(): void
    {
        $event = $this->makeEvent(new AccessDeniedException('Not a member'));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('FORBIDDEN', $payload['error']['code']);
        self::assertSame('Not a member', $payload['error']['message']);
    }

    public function testShouldReturn403JsonWhenAccessDeniedHttpException(): void
    {
        $event = $this->makeEvent(new AccessDeniedHttpException('Nope'));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame('FORBIDDEN', $this->decode($response)['error']['code']);
    }

    public function testShouldReturn401JsonWhenAuthenticationException(): void
    {
        $event = $this->makeEvent(new class ('Invalid credentials') extends AuthenticationException {});
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('UNAUTHORIZED', $payload['error']['code']);
        self::assertSame('Invalid credentials', $payload['error']['message']);
    }

    public function testShouldReturnMappedHttpStatusWhenGenericHttpException(): void
    {
        $event = $this->makeEvent(new ConflictHttpException('Duplicate key'));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(409, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('HTTP_409', $payload['error']['code']);
        self::assertSame('Duplicate key', $payload['error']['message']);
    }

    public function testShouldReturn500WithMaskedMessageWhenGenericExceptionInProd(): void
    {
        $event = $this->makeEvent(new RuntimeException('SQLSTATE[42S02] secret table name'));
        $sut = new ExceptionListener(debug: false);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('INTERNAL_ERROR', $payload['error']['code']);
        self::assertSame('Internal server error', $payload['error']['message']);
        self::assertArrayNotHasKey('details', $payload['error']);
    }

    public function testShouldReturn500WithRealMessageAndTraceWhenGenericExceptionInDev(): void
    {
        $exception = new RuntimeException('Query failed on table "projects"');
        $event = $this->makeEvent($exception);
        $sut = new ExceptionListener(debug: true);

        $sut($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        $payload = $this->decode($response);
        self::assertSame('INTERNAL_ERROR', $payload['error']['code']);
        self::assertSame('Query failed on table "projects"', $payload['error']['message']);
        $details = $payload['error']['details'];
        self::assertIsArray($details);
        self::assertSame(RuntimeException::class, $details['exception']);
        self::assertIsArray($details['trace']);
        self::assertLessThanOrEqual(10, count($details['trace']));
    }

    private function makeEvent(Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }

    /**
     * @return array{error: array<string, mixed>}
     */
    private function decode(JsonResponse $response): array
    {
        /** @var array{error: array<string, mixed>} $decoded */
        $decoded = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
