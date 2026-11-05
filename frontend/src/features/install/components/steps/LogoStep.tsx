import { ImagePlus, X } from 'lucide-react';
import { useRef } from 'react';

import type { LogoState } from '../../types';

interface LogoStepProps {
  value: LogoState;
  onChange(next: LogoState): void;
}

export function LogoStep({ value, onChange }: LogoStepProps) {
  const inputRef = useRef<HTMLInputElement>(null);

  function handleFile(file: File | null) {
    if (value.previewUrl) {
      URL.revokeObjectURL(value.previewUrl);
    }

    if (!file) {
      onChange({ file: null, previewUrl: null });
      return;
    }

    onChange({ file, previewUrl: URL.createObjectURL(file) });
  }

  return (
    <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
      <div className="flex items-center gap-3 border-b border-sc-border pb-5">
        <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
          <ImagePlus size={18} aria-hidden />
        </span>
        <div>
          <h3 className="text-base font-semibold text-sc-text">Logo</h3>
          <p className="text-xs text-sc-text-muted">
            PNG or SVG works best. You can replace it any time from the admin panel.
          </p>
        </div>
      </div>

      <div className="mt-6 flex flex-col items-start gap-4">
        {value.previewUrl ? (
          <div className="flex items-center gap-4">
            <img
              src={value.previewUrl}
              alt="Selected logo preview"
              className="h-20 w-20 rounded-lg border border-sc-border bg-sc-bg object-contain p-2"
            />
            <button
              type="button"
              onClick={() => handleFile(null)}
              className="inline-flex items-center gap-1 rounded-md border border-sc-border bg-sc-bg px-3 py-1.5 text-xs font-medium text-sc-text transition-colors hover:bg-sc-bg-secondary"
            >
              <X size={14} aria-hidden /> Remove
            </button>
          </div>
        ) : null}

        <label
          htmlFor="install-logo-input"
          className="inline-flex cursor-pointer items-center gap-2 rounded-md border border-sc-border bg-sc-bg-secondary px-4 py-2 text-sm font-medium text-sc-text transition-colors hover:bg-sc-bg"
        >
          <ImagePlus size={16} aria-hidden />
          {value.file ? 'Pick a different file' : 'Choose logo file'}
        </label>
        <input
          ref={inputRef}
          id="install-logo-input"
          type="file"
          accept="image/png,image/svg+xml,image/jpeg,image/webp"
          className="sr-only"
          onChange={(event) => handleFile(event.target.files?.[0] ?? null)}
        />
        {value.file ? (
          <p className="text-xs text-sc-text-muted">
            Selected: <strong className="text-sc-text">{value.file.name}</strong> (
            {Math.round(value.file.size / 1024)} KB)
          </p>
        ) : (
          <p className="text-xs text-sc-text-muted">
            You can also skip this step — a generic mark will be used until you upload one.
          </p>
        )}
      </div>
    </section>
  );
}
