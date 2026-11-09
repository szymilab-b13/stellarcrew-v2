import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Loader2, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { z } from 'zod';

import { usePageBreadcrumbs } from '@/components/app/useBreadcrumbs';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { ApiError } from '@/lib/apiClient';

import { useWorkspacesList } from '../../api/useWorkspaces';
import { useDeleteProject, useProject, useUpdateProject } from '../api/useProjects';
import { PROJECT_SLUG_REGEX } from '../lib/projectSlug';
import type { Project } from '../types';
import { DeleteProjectConfirmDialog } from './DeleteProjectConfirmDialog';

const settingsSchema = z.object({
  name: z.string().trim().min(1, 'name.required').max(120, 'name.tooLong'),
  slug: z
    .string()
    .trim()
    .min(1, 'slug.required')
    .max(120, 'slug.tooLong')
    .regex(PROJECT_SLUG_REGEX, 'slug.invalid'),
  description: z.string().trim().max(500, 'description.tooLong'),
});

type SettingsFormValues = z.infer<typeof settingsSchema>;

export function ProjectSettingsPage() {
  const { t } = useTranslation();
  const params = useParams<{ wsId: string; projId: string }>();
  const workspaceId = params.wsId ?? null;
  const projectId = params.projId ?? null;
  const navigate = useNavigate();

  const workspacesQuery = useWorkspacesList();
  const workspace = workspacesQuery.data?.find((w) => w.id === workspaceId) ?? null;
  const projectQuery = useProject(projectId);
  const project = projectQuery.data ?? null;

  const updateMutation = useUpdateProject();
  const deleteMutation = useDeleteProject();
  const [toDelete, setToDelete] = useState<Project | null>(null);
  const [savedAt, setSavedAt] = useState<number | null>(null);

  const breadcrumbs = useMemo(
    () => [
      { label: t('workspaces.breadcrumb'), to: '/workspaces' },
      ...(workspace ? [{ label: workspace.name, to: `/workspace/${workspace.id}/projects` }] : []),
      ...(project
        ? [{ label: project.name, to: `/workspace/${workspaceId}/project/${project.id}/overview` }]
        : []),
      { label: t('projectSettings.breadcrumb') },
    ],
    [t, workspace, project, workspaceId],
  );
  usePageBreadcrumbs(breadcrumbs);

  const form = useForm<SettingsFormValues>({
    resolver: zodResolver(settingsSchema),
    defaultValues: { name: '', slug: '', description: '' },
    mode: 'onTouched',
  });

  useEffect(() => {
    if (!project) return;
    form.reset({
      name: project.name,
      slug: project.slug,
      description: project.description ?? '',
    });
  }, [project, form]);

  if (workspaceId === null || projectId === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (workspacesQuery.isSuccess && workspace === null) {
    return <Navigate to="/workspaces" replace />;
  }

  if (
    projectQuery.isError &&
    projectQuery.error instanceof ApiError &&
    projectQuery.error.status === 404
  ) {
    return <Navigate to={`/workspace/${workspaceId}/projects`} replace />;
  }

  const serverMessage = resolveSaveServerMessage(updateMutation.error, t);
  const nameError = form.formState.errors.name?.message;
  const slugError = form.formState.errors.slug?.message;
  const descriptionError = form.formState.errors.description?.message;

  function handleSubmit(values: SettingsFormValues) {
    if (!project) return;
    updateMutation.reset();
    updateMutation.mutate(
      {
        projectId: project.id,
        workspaceId: project.workspaceId,
        input: {
          name: values.name,
          slug: values.slug,
          description: values.description === '' ? null : values.description,
        },
      },
      {
        onSuccess: (updated) => {
          form.reset({
            name: updated.name,
            slug: updated.slug,
            description: updated.description ?? '',
          });
          setSavedAt(Date.now());
        },
      },
    );
  }

  function openDelete() {
    if (!project) return;
    deleteMutation.reset();
    setToDelete(project);
  }

  function closeDelete() {
    setToDelete(null);
  }

  function handleDeleteConfirm() {
    if (!toDelete) return;
    deleteMutation.mutate(
      { projectId: toDelete.id, workspaceId: toDelete.workspaceId },
      {
        onSuccess: () => {
          setToDelete(null);
          navigate(`/workspace/${toDelete.workspaceId}/projects`, {
            state: {
              deletedProjectName: toDelete.name,
            },
            replace: true,
          });
        },
      },
    );
  }

  return (
    <div className="flex w-full flex-col gap-6">
      <header className="flex flex-col gap-1">
        <h1 className="text-2xl font-semibold tracking-tight text-sc-text">
          {t('projectSettings.title')}
        </h1>
        {project ? (
          <p className="text-sm text-sc-text-muted">
            {t('projectSettings.subtitle', { name: project.name })}
          </p>
        ) : null}
      </header>

      {projectQuery.isPending ? (
        <p className="flex items-center gap-2 text-sm text-sc-text-muted">
          <Loader2 className="animate-spin" size={14} aria-hidden />
          {t('projectSettings.loading')}
        </p>
      ) : null}

      {projectQuery.isError &&
      !(projectQuery.error instanceof ApiError && projectQuery.error.status === 404) ? (
        <p
          role="alert"
          className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-900"
        >
          {t('projectSettings.loadError')}
        </p>
      ) : null}

      {project ? (
        <>
          <section
            aria-label={t('projectSettings.form.sectionAria')}
            className="flex flex-col gap-4 rounded-xl border border-sc-border bg-sc-bg-card p-6"
          >
            <form
              noValidate
              className="flex flex-col gap-4"
              onSubmit={form.handleSubmit(handleSubmit)}
            >
              <div className="flex flex-col gap-1.5">
                <label htmlFor="project-settings-name" className="text-xs font-medium text-sc-text">
                  {t('projectSettings.form.nameLabel')}
                </label>
                <Input
                  id="project-settings-name"
                  type="text"
                  autoComplete="off"
                  maxLength={120}
                  invalid={Boolean(nameError)}
                  {...form.register('name')}
                />
                {nameError ? (
                  <p role="alert" className="text-xs text-sc-danger">
                    {t(`projectSettings.form.errors.${nameError}`)}
                  </p>
                ) : null}
              </div>

              <div className="flex flex-col gap-1.5">
                <label htmlFor="project-settings-slug" className="text-xs font-medium text-sc-text">
                  {t('projectSettings.form.slugLabel')}
                </label>
                <Input
                  id="project-settings-slug"
                  type="text"
                  autoComplete="off"
                  maxLength={120}
                  invalid={Boolean(slugError)}
                  {...form.register('slug')}
                />
                <p className="text-xs text-sc-text-muted">{t('projectSettings.form.slugHint')}</p>
                {slugError ? (
                  <p role="alert" className="text-xs text-sc-danger">
                    {t(`projectSettings.form.errors.${slugError}`)}
                  </p>
                ) : null}
              </div>

              <div className="flex flex-col gap-1.5">
                <label
                  htmlFor="project-settings-description"
                  className="text-xs font-medium text-sc-text"
                >
                  {t('projectSettings.form.descriptionLabel')}
                </label>
                <textarea
                  id="project-settings-description"
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
                    {t(`projectSettings.form.errors.${descriptionError}`)}
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

              {savedAt !== null && !updateMutation.isPending && !updateMutation.isError ? (
                <p
                  role="status"
                  className="rounded-md border border-emerald-400/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
                >
                  {t('projectSettings.form.saved')}
                </p>
              ) : null}

              <div className="flex items-center justify-end gap-2">
                <Button
                  type="submit"
                  variant="primary"
                  disabled={updateMutation.isPending || !form.formState.isDirty}
                  trailingIcon={
                    updateMutation.isPending ? (
                      <Loader2 className="animate-spin" size={14} aria-hidden />
                    ) : (
                      <ArrowRight size={14} aria-hidden />
                    )
                  }
                >
                  {updateMutation.isPending
                    ? t('projectSettings.form.saving')
                    : t('projectSettings.form.submit')}
                </Button>
              </div>
            </form>
          </section>

          <section
            aria-label={t('projectSettings.dangerZone.sectionAria')}
            className="flex flex-col gap-3 rounded-xl border border-sc-danger/40 bg-sc-danger/5 p-6"
          >
            <header className="flex flex-col gap-1">
              <h2 className="text-lg font-semibold text-sc-danger">
                {t('projectSettings.dangerZone.title')}
              </h2>
              <p className="text-sm text-sc-text-muted">
                {t('projectSettings.dangerZone.body', { name: project.name })}
              </p>
            </header>
            <div>
              <Button
                variant="destructive"
                onClick={openDelete}
                leadingIcon={<Trash2 size={14} aria-hidden />}
              >
                {t('projectSettings.dangerZone.deleteButton')}
              </Button>
            </div>
          </section>
        </>
      ) : null}

      {toDelete ? (
        <DeleteProjectConfirmDialog
          project={toDelete}
          isSubmitting={deleteMutation.isPending}
          error={deleteMutation.error}
          onClose={closeDelete}
          onConfirm={handleDeleteConfirm}
        />
      ) : null}
    </div>
  );
}

function resolveSaveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 403) return t('projectSettings.form.errors.server.forbidden');
  if (error.status === 404) return t('projectSettings.form.errors.server.notFound');
  if (error.status === 409) return t('projectSettings.form.errors.server.slugConflict');
  if (error.status === 422) return t('projectSettings.form.errors.server.validation');
  return t('projectSettings.form.errors.server.generic');
}
