import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import type { Workspace } from '../types';

import { WorkspaceTile } from './WorkspaceTile';

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
    render(
      <WorkspaceTile
        workspace={{ ...baseWorkspace, role: 'owner' }}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />,
    );

    expect(screen.getByTestId('role-pill-owner')).toBeInTheDocument();
  });

  it('does not render any role pill when the workspace has no role', () => {
    render(<WorkspaceTile workspace={baseWorkspace} onEdit={vi.fn()} onDelete={vi.fn()} />);

    expect(screen.queryByTestId(/role-pill-/)).not.toBeInTheDocument();
  });
});
