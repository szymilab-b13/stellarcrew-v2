import { describe, expect, it } from 'vitest';

describe('sanity', () => {
  it('should return true when arithmetic sanity', () => {
    expect(1 + 1).toBe(2);
  });

  it('should have jsdom environment available', () => {
    expect(typeof window).toBe('object');
    expect(typeof document).toBe('object');
  });
});
