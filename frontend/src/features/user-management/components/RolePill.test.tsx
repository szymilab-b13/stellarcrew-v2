import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import type { WorkspaceRole } from '@/features/workspaces/types';

import { RolePill } from './RolePill';

describe('RolePill', () => {
  it('renders the owner label with amber tokens', () => {
    render(<RolePill role="owner" />);

    const pill = screen.getByTestId('role-pill-owner');
    expect(pill).toHaveTextContent('Owner');
    expect(pill.className).toContain('bg-amber-100');
    expect(pill.className).toContain('text-amber-800');
  });

  it.each<[WorkspaceRole, string]>([
    ['admin', 'bg-blue-100'],
    ['member', 'bg-emerald-100'],
    ['guest', 'bg-slate-100'],
  ])('maps the %s role to its dedicated color tokens', (role, expectedClass) => {
    render(<RolePill role={role} />);

    expect(screen.getByTestId(`role-pill-${role}`).className).toContain(expectedClass);
  });

  it('shrinks typography for the sm variant', () => {
    render(<RolePill role="admin" size="sm" />);

    expect(screen.getByTestId('role-pill-admin').className).toContain('text-[10px]');
  });
});
