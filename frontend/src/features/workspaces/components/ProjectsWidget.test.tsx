import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ProjectsWidget } from './ProjectsWidget';

describe('ProjectsWidget', () => {
  it('should render the Projects title', () => {
    render(<ProjectsWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Projects' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<ProjectsWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render four stubbed project rows', () => {
    render(<ProjectsWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(4);
  });

  it('should render a status badge for each of the four known statuses', () => {
    render(<ProjectsWidget />);

    for (const label of ['ACTIVE', 'PLANNED', 'PAUSED', 'ARCHIVED']) {
      expect(screen.getByText(label)).toBeInTheDocument();
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<ProjectsWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
