<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use DateTimeImmutable;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Chat\Domain\ChatThreadSummary;
use Modules\Chat\Domain\ChatThreadSummaryRepository;

use function usort;

final readonly class GetAgentConversationsQueryHandler
{
    public function __construct(
        private ChatMessageRepository $messages,
        private ChatThreadRepository $threads,
        private ChatSessionRepository $sessions,
        private ChatThreadSummaryRepository $summaries,
    ) {
    }

    /**
     * @return list<AgentConversationView>
     */
    public function __invoke(GetAgentConversationsQuery $query): array
    {
        $threadIds = $this->messages->findDistinctThreadIdsByParticipant($query->agentId);

        $views = [];
        foreach ($threadIds as $threadId) {
            $thread = $this->threads->findById($threadId);
            if (!$thread instanceof ChatThread) {
                continue;
            }

            $session = $this->sessions->findById($thread->sessionId());
            if (!$session instanceof ChatSession) {
                continue;
            }

            if (!$session->ownerUserId()->equals($query->actorId)) {
                continue;
            }

            $summary = $this->summaries->findByThreadId($threadId);

            $views[] = new AgentConversationView(
                threadId: $thread->id(),
                subject: $thread->subject(),
                shortDescription: $summary instanceof ChatThreadSummary ? $summary->shortDescription() : null,
                messageCount: $this->messages->countByThread($threadId),
                lastMessageAt: $this->messages->latestCreatedAtByThread($threadId),
                parentThreadId: $thread->parentThreadId(),
            );
        }

        usort($views, static function (AgentConversationView $left, AgentConversationView $right): int {
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
}
