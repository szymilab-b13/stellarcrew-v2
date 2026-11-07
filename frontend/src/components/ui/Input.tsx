import { forwardRef, type InputHTMLAttributes } from 'react';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  invalid?: boolean;
}

const BASE_CLASSES =
  'w-full rounded-md border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:ring-1';

const VALID_CLASSES = 'border-sc-border focus:border-sc-primary focus:ring-sc-primary/30';

const INVALID_CLASSES = 'border-sc-danger focus:border-sc-danger focus:ring-sc-danger/30';

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { invalid = false, className, ...rest },
  ref,
) {
  const composed = [BASE_CLASSES, invalid ? INVALID_CLASSES : VALID_CLASSES, className]
    .filter(Boolean)
    .join(' ');
  return <input ref={ref} className={composed} aria-invalid={invalid || undefined} {...rest} />;
});
