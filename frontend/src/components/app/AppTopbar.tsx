import type { AuthUser } from '@/features/auth/types';

import { Breadcrumbs } from './Breadcrumbs';
import { UserMenu } from './UserMenu';

interface AppTopbarProps {
  user: AuthUser;
}

export function AppTopbar({ user }: AppTopbarProps) {
  return (
    <header className="sticky top-0 z-10 flex h-12 items-center justify-between gap-4 border-b border-sc-border bg-sc-bg px-4 lg:px-6">
      <Breadcrumbs />
      <UserMenu user={user} />
    </header>
  );
}
