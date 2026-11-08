import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { LastAgentsActivityWidget } from './LastAgentsActivityWidget';

describe('LastAgentsActivityWidget', () => {
  it('should render the Last Agents Activity title', () => {
    render(<LastAgentsActivityWidget />);

    expect(
      screen.getByRole('heading', { level: 2, name: 'Last Agents Activity' }),
    ).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<LastAgentsActivityWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render four stubbed activity rows', () => {
    render(<LastAgentsActivityWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(4);
  });

  it('should render a status badge for each of the three known statuses', () => {
    render(<LastAgentsActivityWidget />);

    for (const label of ['MERGED', 'IN PROGRESS', 'PENDING']) {
      expect(screen.getAllByText(label).length).toBeGreaterThanOrEqual(1);
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<LastAgentsActivityWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
