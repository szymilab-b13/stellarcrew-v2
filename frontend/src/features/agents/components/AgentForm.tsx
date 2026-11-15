import { zodResolver } from '@hookform/resolvers/zod';
import { ArrowRight, Loader2 } from 'lucide-react';
import { forwardRef, type ReactNode, type TextareaHTMLAttributes } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

import { agentFormSchema, type AgentFormValues } from '../lib/agentFormSchema';

interface AgentFormProps {
  defaultValues: AgentFormValues;
  onSubmit: (values: AgentFormValues) => void;
  isSubmitting: boolean;
  serverError: string | null;
  submitLabel: string;
  submittingLabel: string;
  slugLocked?: boolean;
}

export function AgentForm({
  defaultValues,
  onSubmit,
  isSubmitting,
  serverError,
  submitLabel,
  submittingLabel,
  slugLocked = false,
}: AgentFormProps) {
  const { t } = useTranslation();
  const form = useForm<AgentFormValues>({
    resolver: zodResolver(agentFormSchema),
    defaultValues,
    mode: 'onTouched',
  });

  const errors = form.formState.errors;

  return (
    <form noValidate className="flex w-full flex-col gap-5" onSubmit={form.handleSubmit(onSubmit)}>
      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <FormField
          id="agent-form-name"
          label={t('agents.form.nameLabel')}
          errorKey={errors.name?.message}
        >
          <Input
            id="agent-form-name"
            type="text"
            autoComplete="off"
            maxLength={80}
            invalid={Boolean(errors.name)}
            {...form.register('name')}
          />
        </FormField>
        <FormField
          id="agent-form-role"
          label={t('agents.form.roleLabel')}
          errorKey={errors.role?.message}
        >
          <Input
            id="agent-form-role"
            type="text"
            autoComplete="off"
            maxLength={120}
            invalid={Boolean(errors.role)}
            {...form.register('role')}
          />
        </FormField>
      </section>

      <FormField
        id="agent-form-slug"
        label={t('agents.form.slugLabel')}
        hint={slugLocked ? t('agents.form.slugLockedHint') : t('agents.form.slugHint')}
        errorKey={errors.slug?.message}
      >
        <Input
          id="agent-form-slug"
          type="text"
          autoComplete="off"
          maxLength={80}
          readOnly={slugLocked}
          aria-readonly={slugLocked || undefined}
          invalid={Boolean(errors.slug)}
          className={
            slugLocked ? 'cursor-not-allowed bg-sc-bg-muted text-sc-text-muted' : undefined
          }
          {...form.register('slug')}
        />
      </FormField>

      <SectionTextarea
        id="agent-form-role-description"
        label={t('agents.form.roleDescriptionLabel')}
        hint={t('agents.form.roleDescriptionHint')}
        rows={3}
        maxLength={500}
        errorKey={errors.roleDescription?.message}
        {...form.register('roleDescription')}
      />
      <SectionTextarea
        id="agent-form-expertise"
        label={t('agents.form.expertiseLabel')}
        hint={t('agents.form.expertiseHint')}
        rows={6}
        maxLength={2000}
        errorKey={errors.expertise?.message}
        {...form.register('expertise')}
      />
      <SectionTextarea
        id="agent-form-workflow"
        label={t('agents.form.workflowLabel')}
        hint={t('agents.form.workflowHint')}
        rows={6}
        maxLength={2000}
        errorKey={errors.workflow?.message}
        {...form.register('workflow')}
      />
      <SectionTextarea
        id="agent-form-style"
        label={t('agents.form.styleLabel')}
        hint={t('agents.form.styleHint')}
        rows={4}
        maxLength={2000}
        errorKey={errors.style?.message}
        {...form.register('style')}
      />
      <SectionTextarea
        id="agent-form-output-format"
        label={t('agents.form.outputFormatLabel')}
        hint={t('agents.form.outputFormatHint')}
        rows={4}
        maxLength={2000}
        errorKey={errors.outputFormat?.message}
        {...form.register('outputFormat')}
      />

      {serverError ? (
        <p
          role="alert"
          className="rounded-md border border-sc-danger/40 bg-sc-danger/10 px-3 py-2 text-sm text-sc-danger"
        >
          {serverError}
        </p>
      ) : null}

      <div className="flex items-center justify-end">
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
          {isSubmitting ? submittingLabel : submitLabel}
        </Button>
      </div>
    </form>
  );
}

interface FormFieldProps {
  id: string;
  label: string;
  hint?: string;
  errorKey?: string;
  children: ReactNode;
}

function FormField({ id, label, hint, errorKey, children }: FormFieldProps) {
  const { t } = useTranslation();
  return (
    <div className="flex flex-col gap-1.5">
      <label htmlFor={id} className="text-xs font-medium text-sc-text">
        {label}
      </label>
      {children}
      {hint ? <p className="text-xs text-sc-text-muted">{hint}</p> : null}
      {errorKey ? (
        <p role="alert" className="text-xs text-sc-danger">
          {t(`agents.form.errors.${errorKey}`)}
        </p>
      ) : null}
    </div>
  );
}

interface SectionTextareaProps extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> {
  id: string;
  label: string;
  hint: string;
  errorKey?: string;
}

const SectionTextarea = forwardRef<HTMLTextAreaElement, SectionTextareaProps>(
  function SectionTextarea({ id, label, hint, errorKey, className, ...rest }, ref) {
    const { t } = useTranslation();
    const invalid = Boolean(errorKey);
    const composed = [
      'w-full rounded-md border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:ring-1',
      invalid
        ? 'border-sc-danger focus:border-sc-danger focus:ring-sc-danger/30'
        : 'border-sc-border focus:border-sc-primary focus:ring-sc-primary/30',
      className,
    ]
      .filter(Boolean)
      .join(' ');
    return (
      <div className="flex flex-col gap-1.5">
        <label htmlFor={id} className="text-xs font-medium text-sc-text">
          {label}
        </label>
        <textarea
          ref={ref}
          id={id}
          aria-invalid={invalid || undefined}
          className={composed}
          {...rest}
        />
        <p className="text-xs text-sc-text-muted">{hint}</p>
        {errorKey ? (
          <p role="alert" className="text-xs text-sc-danger">
            {t(`agents.form.errors.${errorKey}`)}
          </p>
        ) : null}
      </div>
    );
  },
);
