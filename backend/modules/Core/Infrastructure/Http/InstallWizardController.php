<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use function is_array;
use function is_string;
use function json_decode;

use JsonException;
use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Infrastructure\Branding\BrandingLogoStorage;
use OpenApi\Attributes as OA;

use function sprintf;
use function str_contains;

use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        private BrandingLogoStorage $logos,
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
        summary: 'Install wizard — seed first admin, persist branding/workspace defaults, flip install flag.',
        tags: ['core'],
        responses: [
            new OA\Response(response: 201, description: 'Admin created, system installed'),
            new OA\Response(response: 400, description: 'Invalid payload (bad email, short password, bad locale)'),
            new OA\Response(response: 404, description: 'System already installed — endpoint locked'),
        ],
    )]
    public function install(Request $request): JsonResponse
    {
        $payload = $this->decodePayload($request);

        $email = $this->requireString($payload, 'email');
        $password = $this->requireString($payload, 'password');
        $displayName = $this->optionalString($payload, 'display_name');
        $instanceName = $this->optionalString($payload, 'instance_name');
        $locale = $this->optionalString($payload, 'locale') ?? 'en';
        $workspaceName = $this->optionalString($payload, 'workspace_name');
        $agentSlugs = $this->normaliseAgentSlugs($payload['agents'] ?? null);

        $logoFile = $request->files->get('logo');
        $logoPath = $logoFile instanceof UploadedFile ? $this->logos->store($logoFile) : null;

        ($this->install)(new InstallSystemCommand(
            adminEmail: $email,
            adminPlaintextPassword: $password,
            adminDisplayName: $displayName,
            instanceName: $instanceName,
            locale: $locale,
            workspaceName: $workspaceName,
            selectedAgentSlugs: $agentSlugs,
            logoRelativePath: $logoPath,
        ));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        $contentType = (string) $request->headers->get('Content-Type');

        if (str_contains($contentType, 'multipart/form-data')) {
            /** @var array<string, mixed> $values */
            $values = $request->request->all();

            return $values;
        }

        if ($request->getContent() === '') {
            return [];
        }

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

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalString(array $payload, string $field): ?string
    {
        if (!isset($payload[$field])) {
            return null;
        }

        $value = $payload[$field];

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return list<string>
     */
    private function normaliseAgentSlugs(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $decoded = array_map(trim(...), explode(',', $value));
            }

            $value = $decoded;
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}
