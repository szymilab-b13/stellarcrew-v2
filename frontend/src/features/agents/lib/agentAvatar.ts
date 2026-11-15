export interface AgentAvatarSwatch {
  bgClass: string;
  textClass: string;
}

export interface AgentAvatar extends AgentAvatarSwatch {
  initials: string;
}

export const AGENT_AVATAR_PALETTE: readonly AgentAvatarSwatch[] = [
  { bgClass: 'bg-purple-500', textClass: 'text-white' },
  { bgClass: 'bg-blue-500', textClass: 'text-white' },
  { bgClass: 'bg-emerald-500', textClass: 'text-white' },
  { bgClass: 'bg-amber-500', textClass: 'text-white' },
  { bgClass: 'bg-pink-500', textClass: 'text-white' },
  { bgClass: 'bg-cyan-500', textClass: 'text-white' },
  { bgClass: 'bg-indigo-500', textClass: 'text-white' },
];

export function getAgentAvatarInitials(name: string): string {
  const parts = name
    .trim()
    .split(/\s+/)
    .filter((part) => part.length > 0);
  if (parts.length === 0) {
    return '?';
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export function getAgentAvatarSwatch(slug: string): AgentAvatarSwatch {
  let hash = 0;
  for (let i = 0; i < slug.length; i += 1) {
    hash = (hash * 31 + slug.charCodeAt(i)) | 0;
  }
  const index = Math.abs(hash) % AGENT_AVATAR_PALETTE.length;
  return AGENT_AVATAR_PALETTE[index];
}

export function getAgentAvatar(slug: string, name: string): AgentAvatar {
  return {
    ...getAgentAvatarSwatch(slug),
    initials: getAgentAvatarInitials(name),
  };
}
