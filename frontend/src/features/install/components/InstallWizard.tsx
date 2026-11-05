import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, ShieldAlert, CheckCircle2 } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import { ApiError } from '@/lib/apiClient';

import { useInstallMutation } from '../api/useInstallMutation';
import { useInstallStatus } from '../api/useInstallStatus';

const installFormSchema = z.object({
  email: z.string().email('Enter a valid email address'),
  password: z.string().min(10, 'Password must have at least 10 characters'),
});

type InstallFormValues = z.infer<typeof installFormSchema>;

export function InstallWizard() {
  const status = useInstallStatus();
  const mutation = useInstallMutation();

  const form = useForm<InstallFormValues>({
    resolver: zodResolver(installFormSchema),
    defaultValues: { email: '', password: '' },
    mode: 'onTouched',
  });

  if (mutation.isSuccess) {
    return (
      <section
        aria-labelledby="install-success-heading"
        className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm"
      >
        <div className="flex items-center gap-2">
          <CheckCircle2 className="text-emerald-500" size={20} aria-hidden />
          <h2 id="install-success-heading" className="text-lg font-medium">
            Administrator created
          </h2>
        </div>
        <p className="mt-2 text-sm text-sc-text-muted">
          The admin account is ready and the system is marked as installed.
        </p>
        <a
          href="/"
          className="mt-4 inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary"
        >
          Go to app
        </a>
      </section>
    );
  }

  if (status.isLoading) {
    return (
      <div
        role="status"
        aria-label="Checking install status"
        className="flex items-center justify-center py-16 text-sc-text-muted"
      >
        <Loader2 className="mr-2 animate-spin" size={20} aria-hidden />
        <span>Checking install status…</span>
      </div>
    );
  }

  if (status.isError) {
    return (
      <div
        role="alert"
        className="rounded-lg border border-red-300 bg-red-50 p-5 text-sm text-red-900"
      >
        <div className="flex items-center gap-2 font-medium">
          <ShieldAlert size={18} aria-hidden />
          <span>Could not reach the API</span>
        </div>
        <p className="mt-2">
          The install status endpoint is unavailable. Make sure the backend is running and
          `VITE_API_URL` points at it.
        </p>
      </div>
    );
  }

  if (status.data?.isInstalled) {
    return (
      <section
        aria-labelledby="already-installed-heading"
        className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm"
      >
        <div className="flex items-center gap-2">
          <CheckCircle2 className="text-emerald-500" size={20} aria-hidden />
          <h2 id="already-installed-heading" className="text-lg font-medium">
            System already installed
          </h2>
        </div>
        <p className="mt-2 text-sm text-sc-text-muted">
          An administrator has already been provisioned. Head back to the application to sign in.
        </p>
        <a
          href="/"
          className="mt-4 inline-flex items-center rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary"
        >
          Go to app
        </a>
      </section>
    );
  }

  const serverError = mutation.error instanceof ApiError ? mutation.error : null;
  const serverMessage = resolveServerErrorMessage(serverError);

  return (
    <section
      aria-labelledby="install-wizard-heading"
      className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm"
    >
      <h2 id="install-wizard-heading" className="text-lg font-medium">
        Create administrator account
      </h2>
      <p className="mt-1 text-sm text-sc-text-muted">
        Provide the credentials for the first administrator. This can only be done once.
      </p>

      <form
        className="mt-6 flex flex-col gap-4"
        onSubmit={form.handleSubmit((values) => mutation.mutate(values))}
        noValidate
      >
        <div className="flex flex-col gap-1.5">
          <label htmlFor="install-email" className="text-sm font-medium text-sc-text">
            Email
          </label>
          <input
            id="install-email"
            type="email"
            autoComplete="username"
            aria-invalid={Boolean(form.formState.errors.email)}
            aria-describedby={form.formState.errors.email ? 'install-email-error' : undefined}
            className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
            {...form.register('email')}
          />
          {form.formState.errors.email ? (
            <p id="install-email-error" role="alert" className="text-xs text-red-600">
              {form.formState.errors.email.message}
            </p>
          ) : null}
        </div>

        <div className="flex flex-col gap-1.5">
          <label htmlFor="install-password" className="text-sm font-medium text-sc-text">
            Password
          </label>
          <input
            id="install-password"
            type="password"
            autoComplete="new-password"
            aria-invalid={Boolean(form.formState.errors.password)}
            aria-describedby={form.formState.errors.password ? 'install-password-error' : undefined}
            className="rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
            {...form.register('password')}
          />
          {form.formState.errors.password ? (
            <p id="install-password-error" role="alert" className="text-xs text-red-600">
              {form.formState.errors.password.message}
            </p>
          ) : null}
        </div>

        {serverMessage ? (
          <p role="alert" className="text-sm text-red-600">
            {serverMessage}
          </p>
        ) : null}

        <button
          type="submit"
          disabled={mutation.isPending}
          className="inline-flex items-center justify-center gap-2 rounded-md bg-sc-text px-4 py-2 text-sm font-medium text-sc-bg transition-opacity disabled:opacity-60"
        >
          {mutation.isPending ? (
            <>
              <Loader2 className="animate-spin" size={16} aria-hidden />
              <span>Installing…</span>
            </>
          ) : (
            <span>Install</span>
          )}
        </button>
      </form>
    </section>
  );
}

function resolveServerErrorMessage(error: ApiError | null): string | null {
  if (!error) {
    return null;
  }

  if (error.status === 400) {
    return 'The server rejected the submitted values. Double-check the email and password.';
  }

  if (error.status === 404) {
    return 'The system has already been installed.';
  }

  return 'Something went wrong. Please try again.';
}
