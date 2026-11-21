<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Http;

use OpenApi\Attributes as OA;

use function rtrim;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class InstallerController
{
    public function __construct(
        private string $publicBaseUrl,
    ) {
    }

    #[Route('/install-worker.sh', name: 'workers_installer_script', methods: ['GET'])]
    #[OA\Get(
        path: '/install-worker.sh',
        summary: 'Bash installer script for stellarcrew-worker (dev flow: prints manual steps)',
        tags: ['workers'],
        responses: [
            new OA\Response(response: 200, description: 'Shell script contents'),
        ],
    )]
    public function installerScript(): Response
    {
        $base = rtrim($this->publicBaseUrl, '/');

        $script = <<<BASH
            #!/usr/bin/env bash
            set -euo pipefail

            # Stellarcrew worker installer (dev preview).
            # Production binary + systemd install coming in BL-WI-022..024.

            CODE=""
            NAME="default"
            CWD=""

            while [[ \$# -gt 0 ]]; do
              case "\$1" in
                --code) CODE="\$2"; shift 2 ;;
                --name) NAME="\$2"; shift 2 ;;
                --cwd)  CWD="\$2";  shift 2 ;;
                *) echo "Unknown arg: \$1" >&2; exit 2 ;;
              esac
            done

            if [[ -z "\$CODE" ]]; then
              echo "error: --code <XXXX-XXXX> is required" >&2
              exit 2
            fi

            echo
            echo "========================================================"
            echo " StellarCrew worker installer"
            echo " Backend: {$base}"
            echo " Pairing code: \$CODE"
            echo " Instance: \$NAME"
            [[ -n "\$CWD" ]] && echo " Working dir: \$CWD"
            echo "========================================================"
            echo

            echo "Production binary is not shipped yet (BL-WI-022..024 pending)."
            echo "For the dev install flow, run these commands on this machine:"
            echo
            echo "  git clone git@github.com:Nessandro/stellarcrew-v2-private.git"
            echo "  cd stellarcrew-v2-private/worker"
            echo "  pnpm install"
            echo "  API_BASE_URL={$base} pnpm pair       # paste code \$CODE when asked"
            echo "  API_BASE_URL={$base} pnpm start      # run the worker daemon"
            echo
            echo "Once the daemon starts, the worker appears in /settings/workers."
            BASH;

        return new Response($script, 200, [
            'Content-Type' => 'text/x-shellscript; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
