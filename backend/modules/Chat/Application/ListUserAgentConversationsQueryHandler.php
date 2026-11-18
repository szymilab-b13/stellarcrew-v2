<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use function count;

use DateTimeImmutable;

use function end;

use Modules\Agents\Api\AgentColorPalette;
use Modules\Agents\Api\AgentsApi;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\ChatThreadId;

use function usort;

final readonly class ListUserAgentConversationsQueryHandler
{
    public function __construct(
        private AgentsApi $agents,
        private ChatSessionRepository $sessions,
        private ChatThreadRepository $threads,
        private ChatMessageRepository $messages,
    ) {
    }

    /**
     * @return list<UserAgentConversationView>
     */
    public function __invoke(ListUserAgentConversationsQuery $query): array
    {
        $agents = $this->agents->listForOwner($query->actorId);
        if ($agents === []) {
            return [];
        }

        $session = $this->sessions->findLatestActiveFor($query->actorId);

        $views = [];
        foreach ($agents as $agent) {
            $views[] = $this->buildViewFor($agent, $session);
        }

        usort($views, static function (UserAgentConversationView $left, UserAgentConversationView $right): int {
            $leftAt = $left->lastMessageAt;
            $rightAt = $right->lastMessageAt;

            if (!$leftAt instanceof DateTimeImmutable && !$rightAt instanceof DateTimeImmutable) {
                return 0;
            }

            if (!$leftAt instanceof DateTimeImmutable) {
                return 1;
            }

            if (!$rightAt instanceof DateTimeImmutable) {
                return -1;
            }

            return $rightAt <=> $leftAt;
        });

        return $views;
    }

    private function buildViewFor(AgentView $agent, ?ChatSession $session): UserAgentConversationView
    {
        $color = $agent->color ?? AgentColorPalette::deriveFromSlug($agent->slug);

        if (!$session instanceof ChatSession) {
            return new UserAgentConversationView(
                agentId: $agent->id,
                agentName: $agent->name,
                agentSlug: $agent->slug,
                agentRole: $agent->role,
                lastMessagePreview: null,
                lastMessageAt: null,
                threadCount: 0,
                isNew: true,
                agentColor: $color,
            );
        }

        $threadIds = $this->messages->findDistinctThreadIdsByParticipant($agent->id->value);

        $threadsInSession = [];
        $latestAt = null;
        $latestPreview = null;

        foreach ($threadIds as $threadId) {
            $thread = $this->threads->findById($threadId);
            if (!$thread instanceof ChatThread) {
                continue;
            }

            if (!$thread->sessionId()->equals($session->id())) {
                continue;
            }

            $threadsInSession[] = $threadId;

            $threadLatestAt = $this->messages->latestCreatedAtByThread($threadId);
            if (!$threadLatestAt instanceof DateTimeImmutable) {
                continue;
            }

            if (!$latestAt instanceof DateTimeImmutable || $threadLatestAt > $latestAt) {
                $latestAt = $threadLatestAt;
                $latestPreview = $this->readLatestContent($threadId);
            }
        }

        return new UserAgentConversationView(
            agentId: $agent->id,
            agentName: $agent->name,
            agentSlug: $agent->slug,
            agentRole: $agent->role,
            lastMessagePreview: $latestPreview,
            lastMessageAt: $latestAt,
            threadCount: count($threadsInSession),
            isNew: $threadsInSession === [],
            agentColor: $color,
        );
    }

    private function readLatestContent(ChatThreadId $threadId): ?string
    {
        $messages = $this->messages->findByThread($threadId);
        if ($messages === []) {
            return null;
        }

        $last = end($messages);

        return $last instanceof ChatMessage ? $last->content() : null;
    }
}
