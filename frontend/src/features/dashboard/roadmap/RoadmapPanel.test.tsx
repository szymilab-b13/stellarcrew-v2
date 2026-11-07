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

  it('should render the AI ENGINE milestone as tbd with a discuss-with-Szef note and no tasks', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/AI ENGINE — model orchestration, memory strategy/i);
    await userEvent.click(header);

    expect(screen.getByText(/discuss with Szef/i)).toBeInTheDocument();
    expect(screen.queryByText(/BL-AI-/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /BL-AI-/i })).not.toBeInTheDocument();
  });

  it('should flag the AI ENGINE milestone with a Needs discussion pill next to the status', () => {
    render(<RoadmapPanel />);

    const summary = screen.getByText(/AI ENGINE — model orchestration, memory strategy/i).closest(
      'summary',
    );
    expect(summary).not.toBeNull();
    expect(summary).toHaveTextContent(/needs discussion/i);
  });

  it('should NOT render the Needs discussion pill on milestones without the flag', () => {
    render(<RoadmapPanel />);

    const coreSummary = screen
      .getByText(/CORE — platform foundation/i)
      .closest('summary');
    expect(coreSummary).not.toBeNull();
    expect(coreSummary).not.toHaveTextContent(/needs discussion/i);
  });

  it('should render done tasks with a checked square and planned tasks with an empty square', async () => {
    render(<RoadmapPanel />);
    await userEvent.click(screen.getByText(/CORE — platform foundation/i));

    const doneBoxes = screen.getAllByTestId('task-checkbox-done');
    expect(doneBoxes.length).toBeGreaterThan(0);

    await userEvent.click(screen.getByText(/PERMISSIONS — fine-grained per-workspace access/i));
    const plannedBoxes = screen.getAllByTestId('task-checkbox-planned');
    expect(plannedBoxes.length).toBeGreaterThan(0);
  });

  it('should render a tbd task with a dashed checkbox (BL-AGENTS-006)', async () => {
    render(<RoadmapPanel />);
    await userEvent.click(screen.getByText(/AGENTS — catalog, user agents, assignments, memory/i));

    const dashed = screen.getAllByTestId('task-checkbox-tbd');
    expect(dashed.length).toBeGreaterThan(0);
  });

  it('should render the AUTH refresh tokens milestone as planned with its BL-AUTH-004/005 tasks and no commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/AUTH refresh tokens — rotation \+ revocation/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-AUTH-004')).toBeInTheDocument();
    expect(screen.getByText('BL-AUTH-005')).toBeInTheDocument();

    const description = screen.getByText(/Short-lived access/i);
    expect(description).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-AUTH-00[45]/i })).not.toBeInTheDocument();
  });

  it('should render the CHAT milestone as tbd with a dependency note and no tasks', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/CHAT — conversations between user and agents/i);
    await userEvent.click(header);

    expect(screen.getByText(/Depends on AGENTS and WORKSPACES/i)).toBeInTheDocument();
    expect(screen.queryByText(/BL-CHAT-/i)).not.toBeInTheDocument();
  });

  it('should render non-milestone threads as planned with BL-DX and BL-DOC tasks', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/Non-milestone threads — DX and documentation/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-DX-001')).toBeInTheDocument();
    expect(screen.getByText('BL-DOC-001')).toBeInTheDocument();

    const description = screen.getByText(/ad-hoc fake health checks/i);
    expect(description).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-(DX|DOC)-/i })).not.toBeInTheDocument();
  });
});
