import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { WorkspaceInfoWidget } from './WorkspaceInfoWidget';

describe('WorkspaceInfoWidget', () => {
  it('should render the Workspace Info title', () => {
    render(<WorkspaceInfoWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Workspace Info' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<WorkspaceInfoWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render the sample workspace name next to avatar initials', () => {
    render(<WorkspaceInfoWidget />);

    expect(screen.getByText('Sample Workspace')).toBeInTheDocument();
    expect(screen.getByText('SW')).toBeInTheDocument();
  });

  it('should render four metadata rows (ID, Created, Members, Visibility)', () => {
    render(<WorkspaceInfoWidget />);

    const metadata = screen.getByRole('list');
    expect(within(metadata).getAllByRole('listitem')).toHaveLength(4);
    for (const label of ['ID', 'Created', 'Members', 'Visibility']) {
      expect(within(metadata).getByText(label)).toBeInTheDocument();
    }
  });

  it('should render a disabled Copy ID button and the "Coming after AGENTS milestone" footer', () => {
    render(<WorkspaceInfoWidget />);

    const copyBtn = screen.getByRole('button', { name: /copy workspace id/i });
    expect(copyBtn).toBeDisabled();
    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
