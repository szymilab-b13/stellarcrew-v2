export type WorkspaceRole = 'owner' | 'admin' | 'member' | 'guest';

export interface Workspace {
  id: string;
  name: string;
  slug: string;
  iconColor: string;
  iconInitials: string;
  ownerId: string;
  role?: WorkspaceRole;
}

export interface WorkspaceCreateInput {
  name: string;
  iconColor: string;
  iconInitials: string;
}

export interface WorkspaceUpdateInput {
  name?: string;
  iconColor?: string;
  iconInitials?: string;
}

export interface WorkspaceColorOption {
  key: string;
  hex: string;
  bgClass: string;
  textClass: string;
  ringClass: string;
}

export const WORKSPACE_COLOR_PALETTE: readonly WorkspaceColorOption[] = [
  {
    key: 'purple',
    hex: '#8B5CF6',
    bgClass: 'bg-purple-500',
    textClass: 'text-white',
    ringClass: 'ring-purple-500',
  },
  {
    key: 'blue',
    hex: '#3B82F6',
    bgClass: 'bg-blue-500',
    textClass: 'text-white',
    ringClass: 'ring-blue-500',
  },
  {
    key: 'pink',
    hex: '#EC4899',
    bgClass: 'bg-pink-500',
    textClass: 'text-white',
    ringClass: 'ring-pink-500',
  },
  {
    key: 'amber',
    hex: '#F59E0B',
    bgClass: 'bg-amber-500',
    textClass: 'text-white',
    ringClass: 'ring-amber-500',
  },
  {
    key: 'emerald',
    hex: '#10B981',
    bgClass: 'bg-emerald-500',
    textClass: 'text-white',
    ringClass: 'ring-emerald-500',
  },
  {
    key: 'cyan',
    hex: '#06B6D4',
    bgClass: 'bg-cyan-500',
    textClass: 'text-white',
    ringClass: 'ring-cyan-500',
  },
  {
    key: 'indigo',
    hex: '#6366F1',
    bgClass: 'bg-indigo-500',
    textClass: 'text-white',
    ringClass: 'ring-indigo-500',
  },
  {
    key: 'slate',
    hex: '#64748B',
    bgClass: 'bg-slate-500',
    textClass: 'text-white',
    ringClass: 'ring-slate-500',
  },
];

export const DEFAULT_WORKSPACE_COLOR = WORKSPACE_COLOR_PALETTE[0].hex;

export function deriveInitials(name: string): string {
  const trimmed = name.trim();
  if (trimmed.length === 0) return '';
  const words = trimmed.split(/\s+/).filter((part) => part.length > 0);
  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase();
  }
  return (words[0][0] + words[1][0]).toUpperCase();
}

export function findColorOption(hex: string): WorkspaceColorOption {
  const match = WORKSPACE_COLOR_PALETTE.find(
    (option) => option.hex.toLowerCase() === hex.toLowerCase(),
  );
  return match ?? WORKSPACE_COLOR_PALETTE[0];
}
