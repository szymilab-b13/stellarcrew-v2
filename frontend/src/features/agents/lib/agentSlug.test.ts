import { describe, expect, it } from 'vitest';

import { AGENT_SLUG_REGEX, toAgentSlug } from './agentSlug';

describe('AGENT_SLUG_REGEX', () => {
  it('should accept lowercase kebab slugs', () => {
    expect(AGENT_SLUG_REGEX.test('maja')).toBe(true);
    expect(AGENT_SLUG_REGEX.test('viktor-builder')).toBe(true);
    expect(AGENT_SLUG_REGEX.test('agent-v2-7')).toBe(true);
  });

  it('should reject slugs with uppercase, spaces, leading or trailing dashes', () => {
    expect(AGENT_SLUG_REGEX.test('Maja')).toBe(false);
    expect(AGENT_SLUG_REGEX.test('viktor builder')).toBe(false);
    expect(AGENT_SLUG_REGEX.test('-maja')).toBe(false);
    expect(AGENT_SLUG_REGEX.test('maja-')).toBe(false);
    expect(AGENT_SLUG_REGEX.test('')).toBe(false);
  });
});

describe('toAgentSlug', () => {
  it('should lowercase and replace spaces with dashes', () => {
    expect(toAgentSlug('Viktor Builder')).toBe('viktor-builder');
  });

  it('should map Polish diacritics to ASCII', () => {
    expect(toAgentSlug('Łukasz Żółć')).toBe('lukasz-zolc');
  });

  it('should trim leading and trailing dashes', () => {
    expect(toAgentSlug('  Maja  ')).toBe('maja');
    expect(toAgentSlug('!!!Viktor!!!')).toBe('viktor');
  });

  it('should collapse non-alphanumeric runs into a single dash', () => {
    expect(toAgentSlug('Viktor  &  Diego')).toBe('viktor-diego');
  });

  it('should truncate to 80 characters', () => {
    const long = 'a'.repeat(200);
    expect(toAgentSlug(long)).toHaveLength(80);
  });
});
