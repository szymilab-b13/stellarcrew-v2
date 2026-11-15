import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';

import { CreateAgentChoosePathDialog } from './CreateAgentChoosePathDialog';

function Wrapper({ children }: { children: ReactNode }) {
  return (
    <MemoryRouter initialEntries={['/agents/new']}>
      <Routes>
        <Route path="/agents/new" element={children} />
        <Route path="/agents/new/template" element={<div>template picker</div>} />
        <Route path="/agents/new/scratch" element={<div>scratch form</div>} />
      </Routes>
    </MemoryRouter>
  );
}

describe('CreateAgentChoosePathDialog', () => {
  it('should render the dialog title and two path options', () => {
    render(<CreateAgentChoosePathDialog onClose={() => {}} />, { wrapper: Wrapper });

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: /create a new agent/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /create agent from template/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /create agent from scratch/i })).toBeInTheDocument();
  });

  it('should call onClose when the close button is clicked', async () => {
    const onClose = vi.fn();
    render(<CreateAgentChoosePathDialog onClose={onClose} />, { wrapper: Wrapper });
    const user = userEvent.setup();

    await user.click(screen.getByRole('button', { name: /close/i }));

    expect(onClose).toHaveBeenCalledOnce();
  });

  it('should navigate to /agents/new/template when the template option is clicked', async () => {
    render(<CreateAgentChoosePathDialog onClose={() => {}} />, { wrapper: Wrapper });
    const user = userEvent.setup();

    await user.click(screen.getByRole('button', { name: /create agent from template/i }));

    expect(await screen.findByText('template picker')).toBeInTheDocument();
  });

  it('should navigate to /agents/new/scratch when the scratch option is clicked', async () => {
    render(<CreateAgentChoosePathDialog onClose={() => {}} />, { wrapper: Wrapper });
    const user = userEvent.setup();

    await user.click(screen.getByRole('button', { name: /create agent from scratch/i }));

    expect(await screen.findByText('scratch form')).toBeInTheDocument();
  });
});
