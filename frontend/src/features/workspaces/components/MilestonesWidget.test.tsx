import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { MilestonesWidget } from './MilestonesWidget';

describe('MilestonesWidget', () => {
  it('should render the Milestones title', () => {
    render(<MilestonesWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Milestones' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<MilestonesWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render four stubbed milestone rows', () => {
    render(<MilestonesWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(4);
  });

  it('should render a status badge for each of the four known statuses', () => {
    render(<MilestonesWidget />);

    for (const label of ['DONE', 'IN PROGRESS', 'PLANNED', 'BLOCKED']) {
      expect(screen.getByText(label)).toBeInTheDocument();
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<MilestonesWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
