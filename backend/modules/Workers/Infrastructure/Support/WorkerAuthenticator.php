<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use InvalidArgumentException;

use function is_string;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;

use function preg_match;
use function str_starts_with;
use function substr;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class WorkerAuthenticator
{
    private const string JWT_SUBJECT_PATTERN = '/^worker:([0-9a-fA-F-]{36})$/';

    public function __construct(
        private WorkerRepository $workers,
        private JWTEncoderInterface $jwtEncoder,
    ) {
    }

    public function authenticate(Request $request): Worker
    {
        $authorization = $request->headers->get('Authorization');
        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            throw new AccessDeniedHttpException('Missing Authorization bearer token.');
        }

        $jwt = substr($authorization, 7);
        if ($jwt === '') {
            throw new AccessDeniedHttpException('Empty bearer token.');
        }

        try {
            $payload = $this->jwtEncoder->decode($jwt);
        } catch (JWTDecodeFailureException) {
            throw new AccessDeniedHttpException('Invalid worker JWT.');
        }

        $sub = $payload['sub'] ?? null;
        if (!is_string($sub) || preg_match(self::JWT_SUBJECT_PATTERN, $sub, $matches) !== 1) {
            throw new AccessDeniedHttpException('Worker JWT missing valid sub claim.');
        }

        try {
            $workerId = WorkerId::fromString($matches[1]);
        } catch (InvalidArgumentException) {
            throw new AccessDeniedHttpException('Malformed worker id in JWT.');
        }

        $worker = $this->workers->findById($workerId);
        if (!$worker instanceof Worker) {
            throw new AccessDeniedHttpException('Unknown worker.');
        }

        return $worker;
    }
}
