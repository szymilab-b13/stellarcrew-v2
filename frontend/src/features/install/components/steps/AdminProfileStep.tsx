import { AtSign, KeyRound, UserRound } from 'lucide-react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import type { AdminProfileState } from '../../types';

interface AdminProfileStepProps {
  value: AdminProfileState;
  errors: Partial<Record<keyof AdminProfileState, string>>;
  onChange(field: keyof AdminProfileState, next: string): void;
}

export function AdminProfileStep({ value, errors, onChange }: AdminProfileStepProps) {
  const { t } = useTranslation();

  return (
    <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
      <div className="flex items-center gap-3 border-b border-sc-border pb-5">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
          <UserRound size={18} aria-hidden />
        </span>
        <div>
          <h3 className="text-base font-semibold text-sc-text">
            {t('steps.admin.sectionHeading')}
          </h3>
          <p className="text-xs text-sc-text-muted">{t('steps.admin.sectionSubheading')}</p>
        </div>
      </div>

      <div className="mt-6 grid gap-5">
        <Field
          id="admin-email"
          label={t('steps.admin.email')}
          required
          hint={t('steps.admin.emailHint')}
          error={errors.email}
          icon={<AtSign size={16} aria-hidden />}
        >
          <input
            id="admin-email"
            type="email"
            autoComplete="username"
            placeholder={t('steps.admin.emailPlaceholder')}
            aria-invalid={Boolean(errors.email)}
            value={value.email}
            onChange={(event) => onChange('email', event.target.value)}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
          />
        </Field>

        <Field
          id="admin-password"
          label={t('steps.admin.password')}
          required
          hint={t('steps.admin.passwordHint')}
          error={errors.password}
          icon={<KeyRound size={16} aria-hidden />}
        >
          <input
            id="admin-password"
            type="password"
            autoComplete="new-password"
            aria-invalid={Boolean(errors.password)}
            value={value.password}
            onChange={(event) => onChange('password', event.target.value)}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
          />
        </Field>

        <Field
          id="admin-password-confirm"
          label={t('steps.admin.confirmPassword')}
          required
          error={errors.passwordConfirm}
          icon={<KeyRound size={16} aria-hidden />}
        >
          <input
            id="admin-password-confirm"
            type="password"
            autoComplete="new-password"
            aria-invalid={Boolean(errors.passwordConfirm)}
            value={value.passwordConfirm}
            onChange={(event) => onChange('passwordConfirm', event.target.value)}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
          />
        </Field>

        <Field
          id="admin-display-name"
          label={t('steps.admin.displayName')}
          hint={t('steps.admin.displayNameHint')}
          error={errors.displayName}
          icon={<UserRound size={16} aria-hidden />}
        >
          <input
            id="admin-display-name"
            type="text"
            autoComplete="nickname"
            placeholder={t('steps.admin.displayNamePlaceholder')}
            aria-invalid={Boolean(errors.displayName)}
            value={value.displayName}
            onChange={(event) => onChange('displayName', event.target.value)}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-9 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
          />
        </Field>
      </div>
    </section>
  );
}

interface FieldProps {
  id: string;
  label: string;
  required?: boolean;
  hint?: string;
  error?: string;
  icon: ReactNode;
  children: ReactNode;
}

function Field({ id, label, required, hint, error, icon, children }: FieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-xs font-medium text-sc-text">
        {label}
        {required ? <span className="ml-1 text-red-500">*</span> : null}
      </label>
      <div className="relative">
        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sc-text-muted">
          {icon}
        </span>
        {children}
      </div>
      {error ? (
        <p role="alert" id={`${id}-error`} className="text-xs text-red-600">
          {error}
        </p>
      ) : hint ? (
        <p className="text-xs text-sc-text-muted">{hint}</p>
      ) : null}
    </div>
  );
}
