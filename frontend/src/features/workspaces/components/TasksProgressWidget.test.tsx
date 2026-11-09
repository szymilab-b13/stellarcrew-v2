import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { TasksProgressWidget } from './TasksProgressWidget';

describe('TasksProgressWidget', () => {
  it('should render the Tasks Progress title', () => {
    render(<TasksProgressWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Tasks Progress' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<TasksProgressWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render three stubbed progress segments', () => {
    render(<TasksProgressWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(3);
  });

  it('should render a segment label for each of the three statuses', () => {
    render(<TasksProgressWidget />);

    for (const label of ['In progress', 'Done', 'Blocked']) {
      expect(screen.getByText(label)).toBeInTheDocument();
    }
  });

  it('should render a percentage value per segment', () => {
    render(<TasksProgressWidget />);

    for (const percent of ['45%', '72%', '8%']) {
      expect(screen.getByText(percent)).toBeInTheDocument();
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<TasksProgressWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
