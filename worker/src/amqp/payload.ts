import { z } from 'zod';

const UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iu;

export const AgentReplyPayloadSchema = z.object({
  threadId: z.string().regex(UUID_REGEX),
  workerId: z.string().regex(UUID_REGEX),
  agentId: z.string().regex(UUID_REGEX),
  userMessageId: z.string().regex(UUID_REGEX),
  userContent: z.string().min(1),
  subject: z.string().min(1),
});

export type AgentReplyPayload = z.infer<typeof AgentReplyPayloadSchema>;

export function parseAgentReplyPayload(raw: unknown): AgentReplyPayload {
  return AgentReplyPayloadSchema.parse(raw);
}
