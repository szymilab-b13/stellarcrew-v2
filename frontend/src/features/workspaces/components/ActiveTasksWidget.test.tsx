import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ActiveTasksWidget } from './ActiveTasksWidget';

describe('ActiveTasksWidget', () => {
  it('should render the Active Tasks title', () => {
    render(<ActiveTasksWidget />);

    expect(screen.getByRole('heading', { level: 2, name: 'Active Tasks' })).toBeInTheDocument();
  });

  it('should render the PROTOTYP badge', () => {
    render(<ActiveTasksWidget />);

    expect(screen.getByText('PROTOTYP')).toBeInTheDocument();
  });

  it('should render three stubbed task rows', () => {
    render(<ActiveTasksWidget />);

    const preview = screen.getByRole('list');
    expect(within(preview).getAllByRole('listitem')).toHaveLength(3);
  });

  it('should render the "Coming after AGENTS milestone" footer', () => {
    render(<ActiveTasksWidget />);

    expect(screen.getByText(/Coming after AGENTS milestone/i)).toBeInTheDocument();
  });
});
