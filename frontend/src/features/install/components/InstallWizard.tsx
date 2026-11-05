import { CheckCircle2, Loader2, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { ApiError } from '@/lib/apiClient';

import { useInstallMutation } from '../api/useInstallMutation';
import { useInstallStatus } from '../api/useInstallStatus';
import {
  EMPTY_INSTALL_FORM,
  INSTALL_STEPS,
  type InstallFormState,
  type AdminProfileState,
} from '../types';

import { AdminProfileStep } from './steps/AdminProfileStep';
import { BrandingStep } from './steps/BrandingStep';
import { LogoStep } from './steps/LogoStep';
import { ReviewStep } from './steps/ReviewStep';
import { WorkspaceStep } from './steps/WorkspaceStep';
import { InstallCompleteScreen } from './InstallCompleteScreen';
import { InstallWizardLayout } from './wizard/InstallWizardLayout';

type AdminErrors = Partial<Record<keyof AdminProfileState, string>>;
type WorkspaceErrors = Partial<Record<'name', string>>;

export function InstallWizard() {
  const { t } = useTranslation();
  const status = useInstallStatus();
  const mutation = useInstallMutation();

  const [stepIndex, setStepIndex] = useState(0);
  const [formState, setFormState] = useState<InstallFormState>(EMPTY_INSTALL_FORM);
  const [adminErrors, setAdminErrors] = useState<AdminErrors>({});
  const [workspaceErrors, setWorkspaceErrors] = useState<WorkspaceErrors>({});

  if (mutation.isSuccess) {
    return <InstallCompleteScreen displayName={formState.admin.displayName} />;
  }

  if (status.isLoading) {
    return (
      <div
        role="status"
        aria-label={t('common.loadingStatus')}
        className="flex min-h-screen items-center justify-center text-sc-text-muted"
      >
        <Loader2 className="mr-2 animate-spin" size={20} aria-hidden />
        <span>{t('common.loadingStatus')}</span>
      </div>
    );
  }

  if (status.isError) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-sc-bg px-6 py-12">
        <div
          role="alert"
          className="max-w-lg rounded-lg border border-red-300 bg-red-50 p-5 text-sm text-red-900"
        >
          <div className="flex items-center gap-2 font-medium">
            <ShieldAlert size={18} aria-hidden />
            <span>{t('api.unreachableTitle')}</span>
          </div>
          <p className="mt-2">{t('api.unreachableBody')}</p>
        </div>
      </div>
    );
  }

  if (status.data?.isInstalled) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-sc-bg px-6 py-12">
        <section
          aria-labelledby="already-installed-heading"
          className="max-w-lg rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm"
        >
          <div className="flex items-center gap-2">
            <CheckCircle2 className="text-emerald-500" size={20} aria-hidden />
            <h2 id="already-installed-heading" className="text-lg font-medium">
              {t('alreadyInstalled.heading')}
            </h2>
          </div>
          <p className="mt-2 text-sm text-sc-text-muted">{t('alreadyInstalled.body')}</p>
          <a
            href="/"
            className="mt-4 inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary"
          >
            {t('common.goToApp')}
          </a>
        </section>
      </div>
    );
  }

  const currentStep = INSTALL_STEPS[stepIndex]!;
  const isLastStep = stepIndex === INSTALL_STEPS.length - 1;

  function updateAdmin(field: keyof AdminProfileState, next: string) {
    setFormState((previous) => ({
      ...previous,
      admin: { ...previous.admin, [field]: next },
    }));
    setAdminErrors((previous) => ({ ...previous, [field]: undefined }));
  }

  function validateAdmin(): boolean {
    const errors: AdminErrors = {};
    const { email, password, passwordConfirm } = formState.admin;

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      errors.email = t('steps.admin.errors.email');
    }
    if (password.length < 10) {
      errors.password = t('steps.admin.errors.password');
    }
    if (password !== passwordConfirm) {
      errors.passwordConfirm = t('steps.admin.errors.passwordConfirm');
    }

    setAdminErrors(errors);
    return Object.keys(errors).length === 0;
  }

  function validateWorkspace(): boolean {
    const errors: WorkspaceErrors = {};
    if (!formState.workspace.name.trim()) {
      errors.name = t('steps.workspace.nameError');
    }
    setWorkspaceErrors(errors);
    return Object.keys(errors).length === 0;
  }

  function goNext() {
    if (currentStep.key === 'admin' && !validateAdmin()) return;
    if (currentStep.key === 'workspace' && !validateWorkspace()) return;

    if (isLastStep) {
      mutation.mutate({
        email: formState.admin.email,
        password: formState.admin.password,
      });
      return;
    }

    setStepIndex((previous) => Math.min(previous + 1, INSTALL_STEPS.length - 1));
  }

  function goBack() {
    setStepIndex((previous) => Math.max(previous - 1, 0));
  }

  function jumpTo(target: number) {
    if (target < stepIndex) {
      setStepIndex(target);
    }
  }

  const serverError = mutation.error instanceof ApiError ? mutation.error : null;
  const serverMessage = resolveServerErrorMessage(serverError, t);

  const eyebrow =
    currentStep.key === 'admin'
      ? t('eyebrow.startHere')
      : currentStep.key === 'review'
        ? t('eyebrow.finalCheck')
        : t('eyebrow.setup');
  const title = t(`steps.${currentStep.key}.title`);
  const description = t(`steps.${currentStep.key}.description`);

  return (
    <InstallWizardLayout
      steps={INSTALL_STEPS}
      currentIndex={stepIndex}
      eyebrow={eyebrow}
      title={title}
      description={description}
      canContinue={true}
      isSubmitting={mutation.isPending}
      onBack={goBack}
      onContinue={goNext}
      onJumpBack={jumpTo}
    >
      {currentStep.key === 'admin' ? (
        <AdminProfileStep value={formState.admin} errors={adminErrors} onChange={updateAdmin} />
      ) : null}
      {currentStep.key === 'branding' ? <BrandingStep /> : null}
      {currentStep.key === 'logo' ? (
        <LogoStep
          value={formState.logo}
          onChange={(next) => setFormState((previous) => ({ ...previous, logo: next }))}
        />
      ) : null}
      {currentStep.key === 'workspace' ? (
        <WorkspaceStep
          workspace={formState.workspace}
          selectedAgentSlugs={formState.selectedAgentSlugs}
          onWorkspaceChange={(next) =>
            setFormState((previous) => ({ ...previous, workspace: next }))
          }
          onAgentsChange={(next) =>
            setFormState((previous) => ({ ...previous, selectedAgentSlugs: next }))
          }
          errors={workspaceErrors}
        />
      ) : null}
      {currentStep.key === 'review' ? <ReviewStep value={formState} /> : null}

      {serverMessage ? (
        <p role="alert" className="mt-4 text-sm text-red-600">
          {serverMessage}
        </p>
      ) : null}
    </InstallWizardLayout>
  );
}

function resolveServerErrorMessage(
  error: ApiError | null,
  t: (key: string) => string,
): string | null {
  if (!error) {
    return null;
  }
  if (error.status === 400) {
    return t('api.serverRejected');
  }
  if (error.status === 404) {
    return t('api.alreadyInstalledMessage');
  }
  return t('api.genericError');
}
