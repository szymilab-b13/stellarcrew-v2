import { describe, expect, it } from 'vitest';

import {
  AGENT_AVATAR_PALETTE,
  getAgentAvatar,
  getAgentAvatarInitials,
  getAgentAvatarSwatch,
} from './agentAvatar';

describe('getAgentAvatarInitials', () => {
  it('should return the first two letters uppercased when name is a single word', () => {
    expect(getAgentAvatarInitials('maja')).toBe('MA');
  });

  it('should return first-letter of first and last word for multi-word names', () => {
    expect(getAgentAvatarInitials('Viktor Builder')).toBe('VB');
    expect(getAgentAvatarInitials('Ada Lovelace Analyst')).toBe('AA');
  });

  it('should fall back to ? when name is blank', () => {
    expect(getAgentAvatarInitials('   ')).toBe('?');
  });
});

describe('getAgentAvatarSwatch', () => {
  it('should return a swatch from the palette', () => {
    const swatch = getAgentAvatarSwatch('maja');

    expect(AGENT_AVATAR_PALETTE).toContainEqual(swatch);
  });

  it('should be deterministic for the same slug', () => {
    expect(getAgentAvatarSwatch('viktor')).toEqual(getAgentAvatarSwatch('viktor'));
  });

  it('should produce different swatches for at least two distinct slugs in the sample set', () => {
    const swatches = new Set(
      ['maja', 'viktor', 'luna', 'hefajstos', 'mentor'].map(
        (slug) => getAgentAvatarSwatch(slug).bgClass,
      ),
    );

    expect(swatches.size).toBeGreaterThan(1);
  });
});

describe('getAgentAvatar', () => {
  it('should combine the slug-based swatch with the name-based initials', () => {
    const avatar = getAgentAvatar('maja', 'Maja');

    expect(avatar.initials).toBe('MA');
    expect(AGENT_AVATAR_PALETTE).toContainEqual({
      bgClass: avatar.bgClass,
      textClass: avatar.textClass,
    });
  });
});
