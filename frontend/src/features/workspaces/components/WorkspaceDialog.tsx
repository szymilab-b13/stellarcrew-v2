import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, X } from 'lucide-react';
import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { ApiError } from '@/lib/apiClient';

import {
  DEFAULT_WORKSPACE_COLOR,
  WORKSPACE_COLOR_PALETTE,
  deriveInitials,
  findColorOption,
  type Workspace,
} from '../types';

const workspaceSchema = z.object({
  name: z.string().trim().min(1, 'name.required').max(100, 'name.tooLong'),
  iconColor: z.string().min(1),
  iconInitials: z.string().trim().min(1, 'initials.required').max(3, 'initials.tooLong'),
});

type WorkspaceFormValues = z.infer<typeof workspaceSchema>;

export type WorkspaceDialogMode = 'create' | 'edit';

interface WorkspaceDialogProps {
  mode: WorkspaceDialogMode;
  workspace: Workspace | null;
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onSubmit: (values: WorkspaceFormValues) => void;
}

export function WorkspaceDialog({
  mode,
  workspace,
  isSubmitting,
  error,
  onClose,
  onSubmit,
}: WorkspaceDialogProps) {
  const { t } = useTranslation();

  const form = useForm<WorkspaceFormValues>({
    resolver: zodResolver(workspaceSchema),
    defaultValues: {
      name: workspace?.name ?? '',
      iconColor: workspace?.iconColor ?? DEFAULT_WORKSPACE_COLOR,
      iconInitials: workspace?.iconInitials ?? '',
    },
    mode: 'onTouched',
  });

  const watchedName = useWatch({ control: form.control, name: 'name' });
  const watchedInitials = useWatch({ control: form.control, name: 'iconInitials' });
  const watchedColor = useWatch({ control: form.control, name: 'iconColor' });

  useEffect(() => {
    if (mode !== 'create') return;
    const auto = deriveInitials(watchedName);
    if (auto.length === 0) return;
    if (form.getFieldState('iconInitials').isDirty) return;
    form.setValue('iconInitials', auto, { shouldValidate: false });
  }, [form, mode, watchedName]);

  const colorOption = findColorOption(watchedColor);
  const serverMessage = resolveServerMessage(error, t);
  const titleKey =
    mode === 'create' ? 'workspaces.dialog.createTitle' : 'workspaces.dialog.editTitle';

  const nameError = form.formState.errors.name?.message;
  const initialsError = form.formState.errors.iconInitials?.message;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="workspace-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-md flex-col gap-5 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <h2 id="workspace-dialog-title" className="text-lg font-semibold text-sc-text">
            {t(titleKey)}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="inline-flex h-7 w-7 items-center justify-center rounded-md text-sc-text-muted transition-colors hover:bg-sc-bg hover:text-sc-text"
          >
            <X size={16} aria-hidden />
          </button>
        </header>

        <form noValidate className="flex flex-col gap-4" onSubmit={form.handleSubmit(onSubmit)}>
          <div className="flex items-center gap-3 rounded-md border border-sc-border bg-sc-bg p-3">
            <span
              className={`inline-flex h-12 w-12 flex-none items-center justify-center rounded-lg text-base font-semibold ${colorOption.bgClass} ${colorOption.textClass}`}
              aria-hidden
            >
              {watchedInitials || '–'}
            </span>
            <p className="text-xs text-sc-text-muted">{t('workspaces.dialog.previewHint')}</p>
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="workspace-name" className="text-xs font-medium text-sc-text">
              {t('workspaces.dialog.nameLabel')}
            </label>
            <input
              id="workspace-name"
              type="text"
              autoComplete="off"
              aria-invalid={Boolean(nameError)}
              className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
              {...form.register('name')}
            />
            {nameError ? (
              <p role="alert" className="text-xs text-red-600">
                {t(`workspaces.dialog.errors.${nameError}`)}
              </p>
            ) : null}
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="workspace-initials" className="text-xs font-medium text-sc-text">
              {t('workspaces.dialog.initialsLabel')}
            </label>
            <input
              id="workspace-initials"
              type="text"
              maxLength={3}
              autoComplete="off"
              aria-invalid={Boolean(initialsError)}
              className="w-24 rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm uppercase tracking-widest text-sc-text outline-none transition-colors focus:border-sc-text"
              {...form.register('iconInitials')}
            />
            {initialsError ? (
              <p role="alert" className="text-xs text-red-600">
                {t(`workspaces.dialog.errors.${initialsError}`)}
              </p>
            ) : null}
          </div>

          <fieldset className="flex flex-col gap-2">
            <legend className="text-xs font-medium text-sc-text">
              {t('workspaces.dialog.colorLabel')}
            </legend>
            <div className="flex flex-wrap gap-2">
              {WORKSPACE_COLOR_PALETTE.map((option) => {
                const checked = option.key === watchedColor.toLowerCase();
                return (
                  <label
                    key={option.key}
                    className={`inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md transition-shadow ${option.bgClass} ${
                      checked
                        ? `ring-2 ring-offset-2 ring-offset-sc-bg-card ${option.ringClass}`
                        : ''
                    }`}
                    title={t(`workspaces.dialog.colors.${option.key}`)}
                  >
                    <input
                      type="radio"
                      value={option.key}
                      className="sr-only"
                      aria-label={t(`workspaces.dialog.colors.${option.key}`)}
                      {...form.register('iconColor')}
                    />
                  </label>
                );
              })}
            </div>
          </fieldset>

          {serverMessage ? (
            <p
              role="alert"
              className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
            >
              {serverMessage}
            </p>
          ) : null}

          <div className="flex items-center justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="inline-flex items-center justify-center rounded-md border border-sc-border bg-sc-bg-card px-3 py-2 text-sm font-semibold text-sc-text transition-colors hover:bg-sc-bg"
            >
              {t('common.cancel')}
            </button>
            <button
              type="submit"
              disabled={isSubmitting}
              className="inline-flex items-center justify-center gap-2 rounded-md bg-sc-primary px-4 py-2 text-sm font-semibold text-sc-primary-foreground shadow-sm transition-opacity hover:opacity-90 disabled:opacity-60"
            >
              {isSubmitting ? (
                <>
                  <Loader2 className="animate-spin" size={14} aria-hidden />
                  {t('common.saving')}
                </>
              ) : (
                t(
                  mode === 'create'
                    ? 'workspaces.dialog.submitCreate'
                    : 'workspaces.dialog.submitEdit',
                )
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 422) return t('workspaces.dialog.errors.server.validation');
  if (error.status === 403) return t('workspaces.dialog.errors.server.forbidden');
  return t('workspaces.dialog.errors.server.generic');
}
