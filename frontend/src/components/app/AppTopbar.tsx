import type { AuthUser } from '@/features/auth/types';
import { LanguageSwitcher } from '@/features/settings/LanguageSwitcher';
import { ThemeToggle } from '@/features/theme/ThemeToggle';

import { UserMenu } from './UserMenu';

interface AppTopbarProps {
  user: AuthUser;
}

export function AppTopbar({ user }: AppTopbarProps) {
  return (
    <header className="sticky top-0 z-10 flex h-12 items-center justify-end gap-2 border-b border-sc-border bg-sc-bg px-4 lg:px-6">
      <LanguageSwitcher />
      <ThemeToggle />
      <span className="mx-1 h-6 w-px bg-sc-border" aria-hidden />
      <UserMenu user={user} />
    </header>
  );
}
