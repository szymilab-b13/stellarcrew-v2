import { apiFetch } from '@/lib/apiClient';

import type {
  ChatMessage,
  ChatSession,
  ChatThread,
  ClassifiedBy,
  ContentType,
  EngineType,
  ListAgentConversationsResponse,
  ListAgentThreadsResponse,
  ListMessagesResponse,
  ListThreadsResponse,
  ParticipantType,
  Priority,
} from '../types';

export interface SendMessageInput {
  sessionId: string;
  threadId: string;
  senderType: ParticipantType;
  senderId: string;
  recipientType: ParticipantType;
  recipientId: string;
  subject: string;
  priority: Priority;
  content: string;
  contentType: ContentType;
  metadata: Record<string, unknown>;
  replyToMessageId: string | null;
  engineId: string;
  engineType: EngineType;
  classifiedBy: ClassifiedBy;
  classificationConfidence: number | null;
}

export interface ListMessagesInput {
  threadId: string;
  limit?: number;
  offset?: number;
}

export async function fetchCurrentSession(): Promise<ChatSession> {
  return apiFetch<ChatSession>('/api/chat/sessions/current');
}

export async function fetchThreads(sessionId: string): Promise<ListThreadsResponse> {
  return apiFetch<ListThreadsResponse>(`/api/chat/sessions/${sessionId}/threads`);
}

export async function fetchMessages(input: ListMessagesInput): Promise<ListMessagesResponse> {
  const params = new URLSearchParams();
  if (input.limit !== undefined) params.set('limit', String(input.limit));
  if (input.offset !== undefined) params.set('offset', String(input.offset));
  const suffix = params.toString();
  const query = suffix === '' ? '' : `?${suffix}`;
  return apiFetch<ListMessagesResponse>(`/api/chat/threads/${input.threadId}/messages${query}`);
}

export async function sendMessage(input: SendMessageInput): Promise<ChatMessage> {
  return apiFetch<ChatMessage>('/api/chat/messages', {
    method: 'POST',
    body: JSON.stringify(input),
  });
}

export interface CreateThreadInput {
  sessionId: string;
  subject: string;
  parentThreadId?: string | null;
}

export interface AttachThreadInput {
  threadId: string;
  parentThreadId: string;
}

export async function fetchAgentConversations(): Promise<ListAgentConversationsResponse> {
  return apiFetch<ListAgentConversationsResponse>('/api/chat/conversations');
}

export async function fetchAgentThreads(agentId: string): Promise<ListAgentThreadsResponse> {
  return apiFetch<ListAgentThreadsResponse>(`/api/chat/agents/${agentId}/threads`);
}

export async function createThread(input: CreateThreadInput): Promise<ChatThread> {
  return apiFetch<ChatThread>('/api/chat/threads', {
    method: 'POST',
    body: JSON.stringify({
      sessionId: input.sessionId,
      subject: input.subject,
      parentThreadId: input.parentThreadId ?? null,
    }),
  });
}

export async function attachThread(input: AttachThreadInput): Promise<ChatThread> {
  return apiFetch<ChatThread>(`/api/chat/threads/${input.threadId}/attach`, {
    method: 'POST',
    body: JSON.stringify({ parentThreadId: input.parentThreadId }),
  });
}
