import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { ApiError } from '@/lib/apiClient';

import { ResetPasswordDialog } from './ResetPasswordDialog';

describe('ResetPasswordDialog', () => {
  it('shows a mismatch error when confirm does not match', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <ResetPasswordDialog
        email="target@example.com"
        isSubmitting={false}
        error={null}
        onClose={() => {}}
        onSubmit={onSubmit}
      />,
    );

    await user.type(screen.getByLabelText('New password'), 'brand-new-password-ok');
    await user.type(screen.getByLabelText('Repeat new password'), 'other-password-string');
    await user.click(screen.getByRole('button', { name: /^reset password$/i }));

    expect(screen.getByRole('alert').textContent).toMatch(/passwords do not match/i);
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('shows a too-short error when new password is under 12 chars', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <ResetPasswordDialog
        email="target@example.com"
        isSubmitting={false}
        error={null}
        onClose={() => {}}
        onSubmit={onSubmit}
      />,
    );

    await user.type(screen.getByLabelText('New password'), 'too-short');
    await user.type(screen.getByLabelText('Repeat new password'), 'too-short');
    await user.click(screen.getByRole('button', { name: /^reset password$/i }));

    expect(screen.getByRole('alert').textContent).toMatch(/at least 12/i);
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('submits the new password when valid and matching', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <ResetPasswordDialog
        email="target@example.com"
        isSubmitting={false}
        error={null}
        onClose={() => {}}
        onSubmit={onSubmit}
      />,
    );

    await user.type(screen.getByLabelText('New password'), 'brand-new-password-ok');
    await user.type(screen.getByLabelText('Repeat new password'), 'brand-new-password-ok');
    await user.click(screen.getByRole('button', { name: /^reset password$/i }));

    expect(onSubmit).toHaveBeenCalledWith('brand-new-password-ok');
  });

  it('renders a server error message when a 422 error is passed in', () => {
    render(
      <ResetPasswordDialog
        email="target@example.com"
        isSubmitting={false}
        error={new ApiError(422, { error: 'password_too_short' })}
        onClose={() => {}}
        onSubmit={() => {}}
      />,
    );

    expect(screen.getByRole('alert').textContent).toMatch(/at least 12/i);
  });

  it('closes when Cancel is clicked', async () => {
    const user = userEvent.setup();
    const onClose = vi.fn();

    render(
      <ResetPasswordDialog
        email="target@example.com"
        isSubmitting={false}
        error={null}
        onClose={onClose}
        onSubmit={() => {}}
      />,
    );

    await user.click(screen.getByRole('button', { name: /cancel/i }));
    expect(onClose).toHaveBeenCalledTimes(1);
  });
});
