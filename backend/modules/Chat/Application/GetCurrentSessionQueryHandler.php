<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\Clock;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;

final readonly class GetCurrentSessionQueryHandler
{
    public const string DEFAULT_LABEL = 'Chat';

    public function __construct(
        private ChatSessionRepository $sessions,
        private OrganizationsApi $organizations,
        private Clock $clock,
    ) {
    }

    public function __invoke(GetCurrentSessionQuery $query): ChatSession
    {
        $existing = $this->sessions->findLatestActiveFor($query->actorId);
        if ($existing instanceof ChatSession) {
            return $existing;
        }

        $organization = $this->organizations->findByOwner($query->actorId);
        if (!$organization instanceof OrganizationView) {
            throw OwnerHasNoOrganizationException::forOwner($query->actorId);
        }

        $session = ChatSession::create(
            ChatSessionId::generate(),
            $organization->id,
            $query->actorId,
            self::DEFAULT_LABEL,
            $this->clock->now(),
        );

        $this->sessions->save($session);

        return $session;
    }
}
