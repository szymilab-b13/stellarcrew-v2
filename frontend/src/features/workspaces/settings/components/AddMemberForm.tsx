import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Loader2, Search } from 'lucide-react';
import { useId, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { useVisibleUsers } from '@/features/user-management/api/useVisibleUsers';
import { ApiError } from '@/lib/apiClient';

import { ASSIGNABLE_ROLES, type WorkspaceRole } from '../types';

const addMemberSchema = z.object({
  targetUserId: z.string().uuid('target.required'),
  role: z.enum(['admin', 'member', 'guest']),
});

type AddMemberFormValues = z.infer<typeof addMemberSchema>;

export interface AddMemberValues {
  targetUserId: string;
  targetEmail: string;
  role: Exclude<WorkspaceRole, 'owner'>;
}

interface AddMemberFormProps {
  excludedUserIds: ReadonlySet<string>;
  isSubmitting: boolean;
  error: unknown;
  onSubmit: (values: AddMemberValues) => void;
}

export function AddMemberForm({
  excludedUserIds,
  isSubmitting,
  error,
  onSubmit,
}: AddMemberFormProps) {
  const { t } = useTranslation();
  const searchId = useId();
  const roleId = useId();
  const [query, setQuery] = useState('');
  const [selectedEmail, setSelectedEmail] = useState<string | null>(null);

  const form = useForm<AddMemberFormValues>({
    resolver: zodResolver(addMemberSchema),
    defaultValues: { targetUserId: '', role: 'member' },
    mode: 'onTouched',
  });

  const trimmedQuery = query.trim();
  const visibleUsers = useVisibleUsers(
    trimmedQuery === '' ? null : trimmedQuery,
    trimmedQuery !== '',
  );
  const candidates = (visibleUsers.data ?? []).filter((user) => !excludedUserIds.has(user.id));
  const showSuggestions = trimmedQuery !== '' && selectedEmail === null;
  const notFound =
    showSuggestions && !visibleUsers.isPending && visibleUsers.isSuccess && candidates.length === 0;

  const serverMessage = resolveServerMessage(error, t);

  function selectCandidate(userId: string, email: string) {
    form.setValue('targetUserId', userId, { shouldValidate: true });
    setSelectedEmail(email);
    setQuery(email);
  }

  function clearSelection() {
    form.setValue('targetUserId', '', { shouldValidate: false });
    setSelectedEmail(null);
  }

  function submit(values: AddMemberFormValues) {
    if (!selectedEmail) return;
    onSubmit({
      targetUserId: values.targetUserId,
      targetEmail: selectedEmail,
      role: values.role as Exclude<WorkspaceRole, 'owner'>,
    });
    form.reset({ targetUserId: '', role: 'member' });
    setSelectedEmail(null);
    setQuery('');
  }

  return (
    <form
      noValidate
      onSubmit={form.handleSubmit(submit)}
      className="flex flex-col gap-4 rounded-md border border-sc-border bg-sc-bg-card p-4"
    >
      <h2 className="text-base font-semibold text-sc-text">{t('workspaceMembers.add.title')}</h2>

      <div className="flex flex-col gap-1.5">
        <label htmlFor={searchId} className="text-xs font-medium text-sc-text">
          {t('workspaceMembers.add.email')}
        </label>
        <span className="relative">
          <Search
            size={14}
            className="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sc-text-muted"
            aria-hidden
          />
          <Input
            id={searchId}
            type="search"
            autoComplete="off"
            value={query}
            placeholder={t('workspaceMembers.add.emailPlaceholder')}
            className="pl-8"
            onChange={(event) => {
              setQuery(event.target.value);
              if (selectedEmail !== null) {
                clearSelection();
              }
            }}
          />
        </span>
        {showSuggestions && visibleUsers.isPending ? (
          <p className="text-xs text-sc-text-muted">{t('workspaceMembers.add.loading')}</p>
        ) : null}
        {showSuggestions && candidates.length > 0 ? (
          <ul
            role="listbox"
            aria-label={t('workspaceMembers.add.candidatesAria')}
            className="flex max-h-48 flex-col overflow-y-auto rounded-md border border-sc-border bg-sc-bg"
          >
            {candidates.map((user) => (
              <li key={user.id}>
                <button
                  type="button"
                  role="option"
                  aria-selected={false}
                  onClick={() => selectCandidate(user.id, user.email)}
                  className="w-full px-3 py-2 text-left text-sm text-sc-text transition-colors hover:bg-sc-bg-secondary"
                >
                  {user.email}
                </button>
              </li>
            ))}
          </ul>
        ) : null}
        {notFound ? (
          <p className="text-xs text-sc-text-muted">{t('workspaceMembers.add.notFoundHint')}</p>
        ) : null}
      </div>

      <div className="flex flex-col gap-1.5">
        <label htmlFor={roleId} className="text-xs font-medium text-sc-text">
          {t('workspaceMembers.add.role')}
        </label>
        <Controller
          control={form.control}
          name="role"
          render={({ field }) => (
            <select
              id={roleId}
              {...field}
              className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text focus:outline-none focus:ring-1 focus:ring-sc-primary/30"
            >
              {ASSIGNABLE_ROLES.map((role) => (
                <option key={role} value={role}>
                  {t(`workspaceMembers.roles.${role}`)}
                </option>
              ))}
            </select>
          )}
        />
      </div>

      {serverMessage ? (
        <p
          role="alert"
          className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
        >
          {serverMessage}
        </p>
      ) : null}

      <div className="flex items-center justify-end">
        <Button
          type="submit"
          variant="primary"
          disabled={isSubmitting || selectedEmail === null}
          trailingIcon={
            isSubmitting ? (
              <Loader2 className="animate-spin" size={14} aria-hidden />
            ) : (
              <ArrowRight size={14} aria-hidden />
            )
          }
        >
          {isSubmitting ? t('common.saving') : t('workspaceMembers.add.submit')}
        </Button>
      </div>
    </form>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 409) return t('workspaceMembers.add.errors.alreadyMember');
  if (error.status === 403) return t('workspaceMembers.add.errors.forbidden');
  if (error.status === 422) return t('workspaceMembers.add.errors.validation');
  return t('workspaceMembers.add.errors.generic');
}
