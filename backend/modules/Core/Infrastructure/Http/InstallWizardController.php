<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http;

use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Infrastructure\Branding\BrandingLogoStorage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;

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
            new OA\Response(response: 422, description: 'Validation error (field list in details)'),
            new OA\Response(response: 400, description: 'Invalid install input (domain-level check)'),
            new OA\Response(response: 404, description: 'System already installed — endpoint locked'),
        ],
    )]
    public function install(
        #[MapRequestPayload]
        InstallRequest $input,
        #[MapUploadedFile(
            constraints: [
                new Assert\File(
                    maxSize: '5M',
                    mimeTypes: ['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'],
                ),
            ],
        )]
        ?UploadedFile $logo = null,
    ): JsonResponse {
        $logoPath = $logo instanceof UploadedFile ? $this->logos->store($logo) : null;

        ($this->install)(new InstallSystemCommand(
            adminEmail: $input->email,
            adminPlaintextPassword: $input->password,
            adminDisplayName: $input->displayName,
            instanceName: $input->instanceName,
            locale: $input->locale,
            workspaceName: $input->workspaceName,
            selectedAgentSlugs: $input->agents,
            logoRelativePath: $logoPath,
        ));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }
}
