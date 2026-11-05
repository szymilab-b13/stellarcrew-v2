<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use function is_string;

use JsonException;
use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use OpenApi\Attributes as OA;

use function sprintf;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class InstallWizardController
{
    public function __construct(
        private IsInstalledQueryHandler $isInstalled,
        private InstallSystemCommandHandler $install,
    ) {
    }

    #[Route('/api/install/status', name: 'core_install_status', methods: ['GET'])]
    #[OA\Get(
        path: '/api/install/status',
        summary: 'Install wizard — read is_installed flag (used by frontend to decide wizard vs login).',
        tags: ['core'],
        responses: [
            new OA\Response(response: 200, description: 'Current install state'),
        ],
    )]
    public function status(): JsonResponse
    {
        return new JsonResponse(['is_installed' => ($this->isInstalled)()]);
    }

    #[Route('/api/install', name: 'core_install', methods: ['POST'])]
    #[OA\Post(
        path: '/api/install',
        summary: 'Install wizard — seed first admin and mark system as installed.',
        tags: ['core'],
        responses: [
            new OA\Response(response: 201, description: 'Admin created, system installed'),
            new OA\Response(response: 400, description: 'Invalid payload (bad email or short password)'),
            new OA\Response(response: 404, description: 'System already installed — endpoint locked'),
        ],
    )]
    public function install(Request $request): JsonResponse
    {
        $payload = $this->decodePayload($request);
        $email = $this->requireString($payload, 'email');
        $password = $this->requireString($payload, 'password');

        ($this->install)(new InstallSystemCommand($email, $password));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new BadRequestHttpException('Invalid JSON payload.', $jsonException);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $field): string
    {
        if (!isset($payload[$field]) || !is_string($payload[$field]) || $payload[$field] === '') {
            throw new BadRequestHttpException(sprintf('Field "%s" is required and must be a non-empty string.', $field));
        }

        return $payload[$field];
    }
}
