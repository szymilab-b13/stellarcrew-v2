import { render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';

import type { Workspace } from '../types';

import { WorkspaceTile } from './WorkspaceTile';

function renderInRouter(node: ReactNode) {
  return render(<MemoryRouter>{node}</MemoryRouter>);
}

const baseWorkspace: Workspace = {
  id: '11111111-1111-1111-1111-111111111111',
  name: 'Default',
  slug: 'default',
  iconColor: '#8B5CF6',
  iconInitials: 'DE',
  ownerId: '00000000-0000-0000-0000-000000000001',
};

describe('WorkspaceTile', () => {
  it('renders the role pill when a role is provided', () => {
    renderInRouter(
      <WorkspaceTile
        workspace={{ ...baseWorkspace, role: 'owner' }}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />,
    );

    expect(screen.getByTestId('role-pill-owner')).toBeInTheDocument();
  });

  it('does not render any role pill when the workspace has no role', () => {
    renderInRouter(<WorkspaceTile workspace={baseWorkspace} onEdit={vi.fn()} onDelete={vi.fn()} />);

    expect(screen.queryByTestId(/role-pill-/)).not.toBeInTheDocument();
  });

  it('renders a members link pointing to the workspace settings members page', () => {
    renderInRouter(<WorkspaceTile workspace={baseWorkspace} onEdit={vi.fn()} onDelete={vi.fn()} />);

    const link = screen.getByRole('link', { name: /manage members of workspace default/i });
    expect(link).toHaveAttribute('href', `/workspace/${baseWorkspace.id}/settings/members`);
  });

  it('renders an overview link wrapping the tile heading', () => {
    renderInRouter(<WorkspaceTile workspace={baseWorkspace} onEdit={vi.fn()} onDelete={vi.fn()} />);

    const link = screen.getByRole('link', { name: /open workspace default/i });
    expect(link).toHaveAttribute('href', `/workspace/${baseWorkspace.id}/overview`);
  });
});
