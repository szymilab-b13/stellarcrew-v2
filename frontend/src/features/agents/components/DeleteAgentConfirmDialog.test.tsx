import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';
import { ApiError } from '@/lib/apiClient';

import type { Agent } from '../types';
import { DeleteAgentConfirmDialog } from './DeleteAgentConfirmDialog';

const agent: Agent = {
  id: '77777777-7777-4777-8777-777777777777',
  organizationId: '22222222-2222-4222-8222-222222222222',
  ownerUserId: '33333333-3333-4333-8333-333333333333',
  sourceTemplateId: null,
  slug: 'custom-one',
  name: 'Custom One',
  role: 'Research Lead',
  roleDescription: 'Keeps all research threads connected.',
  expertise: 'Discovery, interviews, synthesis.',
  workflow: 'Plan, interview, synthesize, report.',
  style: 'Curious and precise.',
  outputFormat: 'TL;DR + highlights + raw notes.',
  isActive: true,
  color: '#6366f1',
  createdAt: '2026-11-15T15:30:00+00:00',
  updatedAt: '2026-11-15T15:30:00+00:00',
};

describe('DeleteAgentConfirmDialog', () => {
  it('should render the dialog with the agent name in body copy', () => {
    render(
      <DeleteAgentConfirmDialog
        agent={agent}
        isSubmitting={false}
        error={null}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
      />,
    );

    expect(screen.getByRole('heading', { name: 'Delete agent' })).toBeInTheDocument();
    expect(screen.getByText(/You are about to delete "Custom One"/)).toBeInTheDocument();
  });

  it('should keep the confirm button disabled until the acknowledge checkbox is checked', async () => {
    render(
      <DeleteAgentConfirmDialog
        agent={agent}
        isSubmitting={false}
        error={null}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
      />,
    );

    const user = userEvent.setup();
    const confirm = screen.getByRole('button', { name: 'Delete agent' });
    expect(confirm).toBeDisabled();

    await user.click(screen.getByRole('checkbox'));
    expect(confirm).toBeEnabled();
  });

  it('should call onConfirm when confirm is clicked after acknowledgement', async () => {
    const onConfirm = vi.fn();
    render(
      <DeleteAgentConfirmDialog
        agent={agent}
        isSubmitting={false}
        error={null}
        onClose={vi.fn()}
        onConfirm={onConfirm}
      />,
    );

    const user = userEvent.setup();
    await user.click(screen.getByRole('checkbox'));
    await user.click(screen.getByRole('button', { name: 'Delete agent' }));

    expect(onConfirm).toHaveBeenCalledTimes(1);
  });

  it('should render a forbidden server message when a 403 error is passed', () => {
    const error = new ApiError(403, { error: 'forbidden' });
    render(
      <DeleteAgentConfirmDialog
        agent={agent}
        isSubmitting={false}
        error={error}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
      />,
    );

    expect(screen.getByRole('alert')).toHaveTextContent(/do not have permission/i);
  });
});
