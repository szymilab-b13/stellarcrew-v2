import { useTranslation } from 'react-i18next';

import type { WorkspaceRole } from '@/features/workspaces/types';

interface RolePillProps {
  role: WorkspaceRole;
  size?: 'sm' | 'md';
}

const COLOR_CLASS: Record<WorkspaceRole, string> = {
  owner: 'bg-amber-100 text-amber-800 ring-amber-200',
  admin: 'bg-blue-100 text-blue-800 ring-blue-200',
  member: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
  guest: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const SIZE_CLASS: Record<'sm' | 'md', string> = {
  sm: 'px-1.5 py-0.5 text-[10px]',
  md: 'px-2 py-0.5 text-xs',
};

export function RolePill({ role, size = 'md' }: RolePillProps) {
  const { t } = useTranslation();

  return (
    <span
      data-testid={`role-pill-${role}`}
      className={`inline-flex items-center rounded-full font-medium ring-1 ring-inset ${COLOR_CLASS[role]} ${SIZE_CLASS[size]}`}
    >
      {t(`workspaceMembers.roles.${role}`)}
    </span>
  );
}
