import { forwardRef, type ButtonHTMLAttributes, type ReactNode } from 'react';

export type ButtonVariant = 'primary' | 'secondary' | 'destructive';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  leadingIcon?: ReactNode;
  trailingIcon?: ReactNode;
}

const BASE_CLASSES =
  'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60';

const VARIANT_CLASSES: Record<ButtonVariant, string> = {
  primary: 'bg-sc-primary text-sc-primary-foreground shadow-sm hover:opacity-90',
  secondary: 'bg-transparent text-sc-text-cancel hover:bg-sc-bg-secondary',
  destructive: 'bg-sc-danger-muted text-sc-primary-foreground shadow-sm hover:bg-sc-danger',
};

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  { variant = 'primary', leadingIcon, trailingIcon, className, children, type = 'button', ...rest },
  ref,
) {
  const composed = [BASE_CLASSES, VARIANT_CLASSES[variant], className].filter(Boolean).join(' ');
  return (
    <button ref={ref} type={type} className={composed} {...rest}>
      {leadingIcon}
      <span>{children}</span>
      {trailingIcon}
    </button>
  );
});
