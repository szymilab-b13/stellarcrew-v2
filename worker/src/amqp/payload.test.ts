import { describe, expect, it } from 'vitest';

import { parseAgentReplyPayload } from './payload.js';

const base = {
  threadId: '01976b7a-1a9d-7e3a-9b7e-27f28cc8d5f0',
  workerId: '01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00',
  agentId: '01976b7a-ab72-7f5e-8a9c-02dc3f4e9c10',
  userMessageId: '01976b7a-ba12-7a6b-9f3b-40ab82c9d100',
  userContent: 'Please draft the release notes.',
  subject: 'Release notes draft',
};

describe('parseAgentReplyPayload', () => {
  it('parses a valid payload', () => {
    expect(parseAgentReplyPayload(base)).toEqual(base);
  });

  it('rejects a missing uuid field', () => {
    const { workerId: _ignored, ...rest } = base;
    expect(() => parseAgentReplyPayload(rest)).toThrow();
  });

  it('rejects a malformed uuid', () => {
    expect(() => parseAgentReplyPayload({ ...base, threadId: 'not-a-uuid' })).toThrow();
  });

  it('rejects empty user content', () => {
    expect(() => parseAgentReplyPayload({ ...base, userContent: '' })).toThrow();
  });

  it('rejects empty subject', () => {
    expect(() => parseAgentReplyPayload({ ...base, subject: '' })).toThrow();
  });

  it('rejects a non-object payload', () => {
    expect(() => parseAgentReplyPayload('nope')).toThrow();
  });
});
