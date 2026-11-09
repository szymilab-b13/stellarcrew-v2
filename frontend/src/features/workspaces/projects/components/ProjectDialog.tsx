import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Loader2, X } from 'lucide-react';
import { useEffect } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { ApiError } from '@/lib/apiClient';

import { PROJECT_SLUG_REGEX, toProjectSlug } from '../lib/projectSlug';

const projectSchema = z.object({
  name: z.string().trim().min(1, 'name.required').max(120, 'name.tooLong'),
  slug: z
    .string()
    .trim()
    .min(1, 'slug.required')
    .max(120, 'slug.tooLong')
    .regex(PROJECT_SLUG_REGEX, 'slug.invalid'),
  description: z.string().trim().max(500, 'description.tooLong'),
});

export type ProjectFormValues = z.infer<typeof projectSchema>;

interface ProjectDialogProps {
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onSubmit: (values: ProjectFormValues) => void;
}

export function ProjectDialog({ isSubmitting, error, onClose, onSubmit }: ProjectDialogProps) {
  const { t } = useTranslation();

  const form = useForm<ProjectFormValues>({
    resolver: zodResolver(projectSchema),
    defaultValues: { name: '', slug: '', description: '' },
    mode: 'onTouched',
  });

  const watchedName = useWatch({ control: form.control, name: 'name' });

  useEffect(() => {
    if (form.getFieldState('slug').isDirty) return;
    const auto = toProjectSlug(watchedName);
    form.setValue('slug', auto, { shouldValidate: false });
  }, [form, watchedName]);

  const serverMessage = resolveServerMessage(error, t);
  const nameError = form.formState.errors.name?.message;
  const slugError = form.formState.errors.slug?.message;
  const descriptionError = form.formState.errors.description?.message;

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="project-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-2xl flex-col gap-5 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <h2 id="project-dialog-title" className="text-lg font-semibold text-sc-text">
            {t('projects.dialog.createTitle')}
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
          <div className="flex flex-col gap-1.5">
            <label htmlFor="project-name" className="text-xs font-medium text-sc-text">
              {t('projects.dialog.nameLabel')}
            </label>
            <Input
              id="project-name"
              type="text"
              autoComplete="off"
              maxLength={120}
              invalid={Boolean(nameError)}
              {...form.register('name')}
            />
            {nameError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`projects.dialog.errors.${nameError}`)}
              </p>
            ) : null}
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="project-slug" className="text-xs font-medium text-sc-text">
              {t('projects.dialog.slugLabel')}
            </label>
            <Input
              id="project-slug"
              type="text"
              autoComplete="off"
              maxLength={120}
              invalid={Boolean(slugError)}
              {...form.register('slug')}
            />
            <p className="text-xs text-sc-text-muted">{t('projects.dialog.slugHint')}</p>
            {slugError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`projects.dialog.errors.${slugError}`)}
              </p>
            ) : null}
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="project-description" className="text-xs font-medium text-sc-text">
              {t('projects.dialog.descriptionLabel')}
            </label>
            <textarea
              id="project-description"
              rows={3}
              maxLength={500}
              aria-invalid={Boolean(descriptionError) || undefined}
              className={[
                'w-full rounded-md border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:ring-1',
                descriptionError
                  ? 'border-sc-danger focus:border-sc-danger focus:ring-sc-danger/30'
                  : 'border-sc-border focus:border-sc-primary focus:ring-sc-primary/30',
              ].join(' ')}
              {...form.register('description')}
            />
            {descriptionError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`projects.dialog.errors.${descriptionError}`)}
              </p>
            ) : null}
          </div>

          {serverMessage ? (
            <p
              role="alert"
              className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
            >
              {serverMessage}
            </p>
          ) : null}

          <div className="flex items-center justify-end gap-2">
            <Button variant="secondary" onClick={onClose}>
              {t('common.cancel')}
            </Button>
            <Button
              type="submit"
              variant="primary"
              disabled={isSubmitting}
              trailingIcon={
                isSubmitting ? (
                  <Loader2 className="animate-spin" size={14} aria-hidden />
                ) : (
                  <ArrowRight size={14} aria-hidden />
                )
              }
            >
              {isSubmitting ? t('common.saving') : t('projects.dialog.submitCreate')}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 422) return t('projects.dialog.errors.server.validation');
  if (error.status === 409) return t('projects.dialog.errors.server.slugConflict');
  if (error.status === 403) return t('projects.dialog.errors.server.forbidden');
  return t('projects.dialog.errors.server.generic');
}
