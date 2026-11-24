export type ParticipantType = 'user' | 'agent' | 'system' | 'supervisor';

export type Priority = 'P0' | 'P1' | 'P2' | 'P3';

export type ContentType =
  'text' | 'markdown' | 'task_dispatch' | 'tool_request' | 'forwarded' | 'notification';

export type EngineType = 'cli_terminal' | 'webhook' | 'batch' | 'stellarcrew_worker';

export type ClassifiedBy = 'sender' | 'supervisor' | 'manual';

export interface ChatSession {
  id: string;
  organizationId: string;
  ownerUserId: string;
  label: string;
  startedAt: string;
  endedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface ChatThread {
  id: string;
  sessionId: string;
  subject: string;
  parentThreadId: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface ChatMessage {
  id: string;
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
  createdAt: string;
}

export interface ListThreadsResponse {
  threads: ChatThread[];
}

export interface ListMessagesResponse {
  messages: ChatMessage[];
  total: number;
  limit: number;
  offset: number;
}

export interface AgentConversation {
  agentId: string;
  agentName: string;
  agentSlug: string;
  agentRole: string;
  agentColor: string;
  lastMessagePreview: string | null;
  lastMessageAt: string | null;
  threadCount: number;
  isNew: boolean;
}

export interface ListAgentConversationsResponse {
  conversations: AgentConversation[];
}

export interface AgentThreadView {
  threadId: string;
  subject: string;
  shortDescription: string | null;
  messageCount: number;
  lastMessageAt: string | null;
  parentThreadId: string | null;
}

export interface ListAgentThreadsResponse {
  threads: AgentThreadView[];
}
