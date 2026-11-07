import { act, render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, describe, expect, it } from 'vitest';

import { Breadcrumbs } from './Breadcrumbs';
import { usePageBreadcrumbs } from './useBreadcrumbs';

function Setter({ segments }: { segments: { label: string; to?: string }[] }) {
  usePageBreadcrumbs(segments);
  return null;
}

function renderWithSegments(segments: { label: string; to?: string }[]) {
  return render(
    <MemoryRouter>
      <Setter segments={segments} />
      <Breadcrumbs />
    </MemoryRouter>,
  );
}

describe('Breadcrumbs', () => {
  afterEach(() => {
    act(() => {
      // Ensure next test starts clean even when unmount cleanup already ran.
    });
  });

  it('should render only the home link when no page segments are provided', () => {
    render(
      <MemoryRouter>
        <Breadcrumbs />
      </MemoryRouter>,
    );

    const home = screen.getByRole('link', { name: /home/i });
    expect(home).toHaveAttribute('href', '/dashboard');
    expect(screen.queryByRole('separator')).not.toBeInTheDocument();
  });

  it('should render a trailing segment marked as the current page', () => {
    renderWithSegments([{ label: 'Workspace’y' }]);

    expect(screen.getByRole('link', { name: /home/i })).toHaveAttribute('href', '/dashboard');
    const current = screen.getByText(/workspace/i);
    expect(current).toHaveAttribute('aria-current', 'page');
  });

  it('should render intermediate segments as links when "to" is provided', () => {
    renderWithSegments([{ label: 'Workspace’y', to: '/workspaces' }, { label: 'Default' }]);

    expect(screen.getByRole('link', { name: /workspace/i })).toHaveAttribute('href', '/workspaces');
    expect(screen.getByText(/default/i)).toHaveAttribute('aria-current', 'page');
  });
});
