<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

final readonly class InstallSystemCommand
{
    /**
     * @param list<string> $selectedAgentSlugs
     */
    public function __construct(
        public string $adminEmail,
        public string $adminPlaintextPassword,
        public ?string $adminDisplayName = null,
        public ?string $instanceName = null,
        public string $locale = 'en',
        public ?string $workspaceName = null,
        public array $selectedAgentSlugs = [],
        public ?string $logoRelativePath = null,
    ) {
    }
}
