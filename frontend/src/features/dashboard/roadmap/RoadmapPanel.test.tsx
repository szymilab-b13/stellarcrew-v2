import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { RoadmapPanel } from './RoadmapPanel';

describe('RoadmapPanel', () => {
  it('should render each milestone collapsed by default', () => {
    render(<RoadmapPanel />);

    const details = screen.getAllByRole('group');
    expect(details.length).toBeGreaterThan(0);
    for (const node of details) {
      expect(node).not.toHaveAttribute('open');
    }
  });

  it('should expand a milestone to reveal its tasks when clicked', async () => {
    render(<RoadmapPanel />);

    await userEvent.click(screen.getByText(/CORE — platform foundation/i));

    expect(screen.getByText(/BL-CORE-002/)).toBeInTheDocument();
    expect(screen.getByText(/HealthController/i)).toBeInTheDocument();
  });

  it('should render done tasks with a strikethrough and a linked commit hash', async () => {
    render(<RoadmapPanel />);
    await userEvent.click(screen.getByText(/CORE — platform foundation/i));

    const commitLink = screen.getByRole('link', { name: /f52b949/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/f52b949',
    );

    const description = screen.getByText(/User moved from Core to Auth/i);
    expect(description).toHaveClass('line-through');
  });

  it('should render the AUTH milestone with its BL-AUTH tasks and commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/AUTH — first login \(JWT\)/i);
    await userEvent.click(header);

    expect(screen.getByText(/BL-AUTH-001/)).toBeInTheDocument();
    expect(screen.getByText(/BL-AUTH-002/)).toBeInTheDocument();
    expect(screen.getByText(/BL-AUTH-003/)).toBeInTheDocument();

    const commitLink = screen.getByRole('link', { name: /ac605d5/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/ac605d5',
    );

    const description = screen.getByText(/lexik\/jwt-authentication-bundle/i);
    expect(description).toHaveClass('line-through');
  });

  it('should render the UI milestone with its BL-UI tasks and commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/UI — app shell, menu, Design 2.0 polish/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-UI-001')).toBeInTheDocument();
    expect(screen.getByText('BL-UI-004')).toBeInTheDocument();
    expect(screen.getByText('BL-UI-POLISH-001')).toBeInTheDocument();

    const commitLink = screen.getByRole('link', { name: /ad1ac4d/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/ad1ac4d',
    );

    const description = screen.getByText(/Backend menu registry/i);
    expect(description).toHaveClass('line-through');
  });

  it('should render the WORKSPACES milestone with its BL-WS tasks and commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/WORKSPACES — multi-tenant workspaces/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-WS-001')).toBeInTheDocument();
    expect(screen.getByText('BL-WS-005')).toBeInTheDocument();
    expect(screen.getByText('BL-WS-HOTFIX-002')).toBeInTheDocument();

    const commitLink = screen.getByRole('link', { name: /aced1c8/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/aced1c8',
    );

    const description = screen.getByText(/Workspaces module/i);
    expect(description).toHaveClass('line-through');
  });

  it('should render the PERMISSIONS milestone as planned with its BL-PERM tasks and no commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/PERMISSIONS — fine-grained per-workspace access/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-PERM-001')).toBeInTheDocument();
    expect(screen.getByText('BL-PERM-002')).toBeInTheDocument();
    expect(screen.getByText('BL-PERM-003')).toBeInTheDocument();

    const description = screen.getByText(/Permission enum per module/i);
    expect(description).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-PERM-/i })).not.toBeInTheDocument();
  });

  it('should render the AGENTS milestone as planned with its BL-AGENTS tasks and no commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/AGENTS — catalog, user agents, assignments, memory/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-AGENTS-001')).toBeInTheDocument();
    expect(screen.getByText('BL-AGENTS-002')).toBeInTheDocument();
    expect(screen.getByText('BL-AGENTS-003')).toBeInTheDocument();
    expect(screen.getByText('BL-AGENTS-007')).toBeInTheDocument();

    const description = screen.getByText(/Predefined agents catalog/i);
    expect(description).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-AGENTS-/i })).not.toBeInTheDocument();
  });
});
