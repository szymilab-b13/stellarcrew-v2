import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Loader2, X } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { ApiError } from '@/lib/apiClient';

const addUserSchema = z
  .object({
    email: z.string().trim().email('email.invalid'),
    password: z.string().min(12, 'password.tooShort'),
    passwordConfirm: z.string(),
    isSuperAdmin: z.boolean(),
  })
  .refine((data) => data.password === data.passwordConfirm, {
    path: ['passwordConfirm'],
    message: 'passwordConfirm.mismatch',
  });

type AddUserFormValues = z.infer<typeof addUserSchema>;

export interface AddUserValues {
  email: string;
  password: string;
  isSuperAdmin: boolean;
}

interface AddUserDialogProps {
  isSubmitting: boolean;
  error: unknown;
  onClose: () => void;
  onSubmit: (values: AddUserValues) => void;
}

export function AddUserDialog({ isSubmitting, error, onClose, onSubmit }: AddUserDialogProps) {
  const { t } = useTranslation();

  const form = useForm<AddUserFormValues>({
    resolver: zodResolver(addUserSchema),
    defaultValues: { email: '', password: '', passwordConfirm: '', isSuperAdmin: false },
    mode: 'onTouched',
  });

  const emailError = form.formState.errors.email?.message;
  const passwordError = form.formState.errors.password?.message;
  const passwordConfirmError = form.formState.errors.passwordConfirm?.message;
  const serverMessage = resolveServerMessage(error, t);

  function submit(values: AddUserFormValues) {
    onSubmit({
      email: values.email,
      password: values.password,
      isSuperAdmin: values.isSuperAdmin,
    });
  }

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="add-user-dialog-title"
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div className="flex w-full max-w-2xl flex-col gap-5 rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-xl">
        <header className="flex items-start justify-between gap-3">
          <h2 id="add-user-dialog-title" className="text-lg font-semibold text-sc-text">
            {t('userManagement.addDialog.title')}
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

        <form noValidate className="flex flex-col gap-4" onSubmit={form.handleSubmit(submit)}>
          <div className="flex flex-col gap-1.5">
            <label htmlFor="add-user-email" className="text-xs font-medium text-sc-text">
              {t('userManagement.addDialog.email')}
            </label>
            <Input
              id="add-user-email"
              type="email"
              autoComplete="off"
              invalid={Boolean(emailError)}
              {...form.register('email')}
            />
            {emailError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`userManagement.addDialog.errors.${emailError}`)}
              </p>
            ) : null}
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="add-user-password" className="text-xs font-medium text-sc-text">
              {t('userManagement.addDialog.password')}
            </label>
            <Input
              id="add-user-password"
              type="password"
              autoComplete="new-password"
              invalid={Boolean(passwordError)}
              {...form.register('password')}
            />
            <p className="text-[11px] text-sc-text-muted">
              {t('userManagement.addDialog.passwordHint')}
            </p>
            {passwordError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`userManagement.addDialog.errors.${passwordError}`)}
              </p>
            ) : null}
          </div>

          <div className="flex flex-col gap-1.5">
            <label htmlFor="add-user-password-confirm" className="text-xs font-medium text-sc-text">
              {t('userManagement.addDialog.passwordConfirm')}
            </label>
            <Input
              id="add-user-password-confirm"
              type="password"
              autoComplete="new-password"
              invalid={Boolean(passwordConfirmError)}
              {...form.register('passwordConfirm')}
            />
            {passwordConfirmError ? (
              <p role="alert" className="text-xs text-sc-danger">
                {t(`userManagement.addDialog.errors.${passwordConfirmError}`)}
              </p>
            ) : null}
          </div>

          <label className="flex items-center gap-2 text-sm text-sc-text">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-sc-border text-sc-primary focus:ring-sc-primary/30"
              {...form.register('isSuperAdmin')}
            />
            {t('userManagement.addDialog.isSuperAdmin')}
          </label>

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
              {isSubmitting ? t('common.saving') : t('userManagement.addDialog.submit')}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
}

function resolveServerMessage(error: unknown, t: (key: string) => string): string | null {
  if (!(error instanceof ApiError)) return null;
  if (error.status === 422) return t('userManagement.addDialog.errors.server.validation');
  if (error.status === 409) return t('userManagement.addDialog.errors.server.emailTaken');
  if (error.status === 403) return t('userManagement.addDialog.errors.server.forbidden');
  return t('userManagement.addDialog.errors.server.generic');
}
