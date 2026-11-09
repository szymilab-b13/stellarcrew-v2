import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ActiveAgentsWidget } from './ActiveAgentsWidget';

describe('ActiveAgentsWidget', () => {
  it('should render the Active Agents title', () => {
    render(<ActiveAgentsWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Active Agents' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<ActiveAgentsWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render four stubbed agent rows', () => {
    render(<ActiveAgentsWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(4);
  });

  it('should render a mode badge for each of the four known modes', () => {
    render(<ActiveAgentsWidget />);

    for (const label of ['BUILDER', 'LISTENER', 'DISCOVERY', 'IDLE']) {
      expect(screen.getByText(label)).toBeInTheDocument();
    }
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<ActiveAgentsWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
