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
use Modules\Workers\Domain\WorkerSecretTokenHasher;

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
        private WorkerSecretTokenHasher $hasher,
        private JWTEncoderInterface $jwtEncoder,
        private bool $legacyAuthEnabled = false,
    ) {
    }

    public function authenticate(Request $request): Worker
    {
        $authorization = $request->headers->get('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return $this->authenticateBearer(substr($authorization, 7));
        }

        if ($this->legacyAuthEnabled) {
            return $this->authenticateLegacy($request);
        }

        throw new AccessDeniedHttpException('Missing Authorization bearer token.');
    }

    private function authenticateBearer(string $jwt): Worker
    {
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

    private function authenticateLegacy(Request $request): Worker
    {
        $workerIdHeader = $request->headers->get('X-Worker-Id');
        $secretHeader = $request->headers->get('X-Worker-Secret');

        if (!is_string($workerIdHeader) || !is_string($secretHeader) || $workerIdHeader === '' || $secretHeader === '') {
            throw new AccessDeniedHttpException('Missing worker credentials.');
        }

        try {
            $workerId = WorkerId::fromString($workerIdHeader);
        } catch (InvalidArgumentException) {
            throw new AccessDeniedHttpException('Malformed X-Worker-Id.');
        }

        $worker = $this->workers->findById($workerId);
        if (!$worker instanceof Worker) {
            throw new AccessDeniedHttpException('Unknown worker.');
        }

        if (!$this->hasher->verify($secretHeader, $worker->secretTokenHash())) {
            throw new AccessDeniedHttpException('Invalid worker credentials.');
        }

        return $worker;
    }
}
