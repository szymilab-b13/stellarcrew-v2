import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { AddUserDialog } from './AddUserDialog';

describe('AddUserDialog', () => {
  it('should show a validation error when the password is shorter than 12 characters', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <AddUserDialog isSubmitting={false} error={null} onClose={() => {}} onSubmit={onSubmit} />,
    );

    await user.type(screen.getByLabelText(/^email$/i), 'new@example.com');
    await user.type(screen.getByLabelText(/^password$/i), 'short-pw');
    await user.type(screen.getByLabelText(/confirm password/i), 'short-pw');
    await user.click(screen.getByRole('button', { name: /^create$/i }));

    expect(
      await screen.findByText(/password must have at least 12 characters/i),
    ).toBeInTheDocument();
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('should submit the values when the form is valid', async () => {
    const user = userEvent.setup();
    const onSubmit = vi.fn();

    render(
      <AddUserDialog isSubmitting={false} error={null} onClose={() => {}} onSubmit={onSubmit} />,
    );

    await user.type(screen.getByLabelText(/^email$/i), 'new@example.com');
    await user.type(screen.getByLabelText(/^password$/i), 'long-enough-password');
    await user.type(screen.getByLabelText(/confirm password/i), 'long-enough-password');
    await user.click(screen.getByLabelText(/grant super admin/i));
    await user.click(screen.getByRole('button', { name: /^create$/i }));

    expect(onSubmit).toHaveBeenCalledWith({
      email: 'new@example.com',
      password: 'long-enough-password',
      isSuperAdmin: true,
    });
  });
});
