import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { ApiError } from '@/lib/apiClient';

import { toProjectSlug } from '../lib/projectSlug';
import { ProjectDialog, type ProjectFormValues } from './ProjectDialog';

function renderDialog(overrides: Partial<Parameters<typeof ProjectDialog>[0]> = {}) {
  const onSubmit = vi.fn<(values: ProjectFormValues) => void>();
  const onClose = vi.fn();
  render(
    <ProjectDialog
      isSubmitting={false}
      error={null}
      onClose={onClose}
      onSubmit={onSubmit}
      {...overrides}
    />,
  );
  return { onSubmit, onClose };
}

describe('toProjectSlug', () => {
  it('should lowercase and replace spaces with hyphens', () => {
    expect(toProjectSlug('Talent Bridge API')).toBe('talent-bridge-api');
  });

  it('should strip polish diacritics', () => {
    expect(toProjectSlug('Żółć Projekt')).toBe('zolc-projekt');
  });

  it('should collapse non-alphanumerics and trim hyphens', () => {
    expect(toProjectSlug('  Hello!! World?? ')).toBe('hello-world');
  });

  it('should return empty string for a blank input', () => {
    expect(toProjectSlug('   ')).toBe('');
  });
});

describe('ProjectDialog', () => {
  it('should live-sync slug from the name field until the user edits slug manually', async () => {
    renderDialog();
    const user = userEvent.setup();

    const nameInput = screen.getByLabelText(/^name$/i);
    const slugInput = screen.getByLabelText(/^slug$/i) as HTMLInputElement;

    await user.type(nameInput, 'Talent Bridge');
    expect(slugInput.value).toBe('talent-bridge');

    await user.clear(slugInput);
    await user.type(slugInput, 'custom-slug');
    await user.type(nameInput, ' Extra');

    expect(slugInput.value).toBe('custom-slug');
  });

  it('should submit the trimmed form values', async () => {
    const { onSubmit } = renderDialog();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/^name$/i), 'Talent Bridge');
    await user.type(screen.getByLabelText(/^description$/i), 'HR integration');
    await user.click(screen.getByRole('button', { name: /^create$/i }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0]?.[0]).toEqual({
      name: 'Talent Bridge',
      slug: 'talent-bridge',
      description: 'HR integration',
    });
  });

  it('should show an inline validation error for a non-kebab slug', async () => {
    const { onSubmit } = renderDialog();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/^name$/i), 'Talent Bridge');
    const slugInput = screen.getByLabelText(/^slug$/i);
    await user.clear(slugInput);
    await user.type(slugInput, 'Invalid Slug!');
    await user.click(screen.getByRole('button', { name: /^create$/i }));

    expect(await screen.findByText(/lowercase letters, digits and single hyphens/i)).toBeVisible();
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('should render the slug-conflict server error message when the API returns 409', () => {
    renderDialog({ error: new ApiError(409, { message: 'slug_taken' }) });

    expect(
      screen.getByText(/project with this slug already exists in the workspace/i),
    ).toBeVisible();
  });

  it('should close when the cancel button is pressed', async () => {
    const { onClose } = renderDialog();
    const user = userEvent.setup();

    await user.click(screen.getByRole('button', { name: /cancel/i }));

    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
