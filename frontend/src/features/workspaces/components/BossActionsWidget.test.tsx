import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { BossActionsWidget } from './BossActionsWidget';

describe('BossActionsWidget', () => {
  it('should render the Boss Actions title', () => {
    render(<BossActionsWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Boss Actions' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<BossActionsWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render four stubbed action rows', () => {
    render(<BossActionsWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(4);
  });

  it('should render an action-type badge for each of the four known types', () => {
    render(<BossActionsWidget />);

    for (const label of ['CREATE', 'INVITE', 'LAUNCH', 'BROADCAST']) {
      expect(screen.getByText(label)).toBeInTheDocument();
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<BossActionsWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
