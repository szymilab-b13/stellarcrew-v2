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
    expect(screen.getByText(/install StellarCrew from the command line/i)).toBeInTheDocument();
  });

  it('should render done tasks with a strikethrough and a linked commit hash', async () => {
    render(<RoadmapPanel />);
    await userEvent.click(screen.getByText(/CORE — platform foundation/i));

    const commitLink = screen.getByRole('link', { name: /f52b949/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/f52b949',
    );

    const story = screen.getByText(/User lives inside the Auth module/i);
    expect(story).toHaveClass('line-through');
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

    const story = screen.getByText(/sign in with e-mail and password/i);
    expect(story).toHaveClass('line-through');
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

    const story = screen.getByText(/sidebar menu assembles from modules/i);
    expect(story).toHaveClass('line-through');
  });

  it('should render the ORGANIZATION milestone with BL-ORG tasks, done commits and deferred planned entries', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/ORGANIZATION — top-level tenant/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-ORG-001')).toBeInTheDocument();
    expect(screen.getByText('BL-ORG-004b')).toBeInTheDocument();
    expect(screen.getByText('BL-ORG-008')).toBeInTheDocument();
    expect(screen.getByText('BL-ORG-010')).toBeInTheDocument();

    const commitLink = screen.getByRole('link', { name: /e2d66f2/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/e2d66f2',
    );

    const doneStory = screen.getByText(/Organization aggregate with OrganizationId VO/i);
    expect(doneStory).toHaveClass('line-through');

    const deferredStory = screen.getByText(/Deferred to Phase 1 — not needed for MVP/i);
    expect(deferredStory).not.toHaveClass('line-through');
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

    const story = screen.getByText(/workspace domain is in place/i);
    expect(story).toHaveClass('line-through');
  });

  it('should render the PERMISSIONS milestone as planned with its BL-PERM tasks and no commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/PERMISSIONS — fine-grained per-workspace access/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-PERM-001')).toBeInTheDocument();
    expect(screen.getByText('BL-PERM-002')).toBeInTheDocument();
    expect(screen.getByText('BL-PERM-003')).toBeInTheDocument();

    const story = screen.getByText(/Per-module permissions/i);
    expect(story).not.toHaveClass('line-through');
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

    const story = screen.getByText(/Admin seeds a catalog of predefined agents/i);
    expect(story).not.toHaveClass('line-through');
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

  it('should render the USER MANAGEMENT milestone as done with BL-UM tasks and no Needs discussion pill', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(
      /USER MANAGEMENT — admin panel for users \+ workspace assignments/i,
    );
    const summary = header.closest('summary');
    expect(summary).not.toBeNull();
    expect(summary).not.toHaveTextContent(/needs discussion/i);

    await userEvent.click(header);

    expect(screen.getByText('BL-UM-001')).toBeInTheDocument();
    expect(screen.getByText('BL-UM-012')).toBeInTheDocument();
    expect(screen.getByText(/Each user has a profile/i)).toBeInTheDocument();
  });

  it('should render the AUDIT milestone with BL-AUDIT tasks', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/AUDIT — audit log \+ undo dispatcher/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-AUDIT-001')).toBeInTheDocument();
    expect(screen.getByText('BL-AUDIT-007')).toBeInTheDocument();
    expect(screen.getByText(/undo an audit entry/i)).toBeInTheDocument();
  });

  it('should flag the AI ENGINE milestone with a Needs discussion pill next to the status', () => {
    render(<RoadmapPanel />);

    const summary = screen
      .getByText(/AI ENGINE — model orchestration, memory strategy/i)
      .closest('summary');
    expect(summary).not.toBeNull();
    expect(summary).toHaveTextContent(/needs discussion/i);
  });

  it('should NOT render the Needs discussion pill on milestones without the flag', () => {
    render(<RoadmapPanel />);

    const coreSummary = screen.getByText(/CORE — platform foundation/i).closest('summary');
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

    const story = screen.getByText(/Short-lived access tokens/i);
    expect(story).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-AUTH-00[45]/i })).not.toBeInTheDocument();
  });

  it('should render the CHAT milestone as done with BL-CHAT backend and frontend tasks and commit links', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/CHAT — conversations between user and agents/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-CHAT-001')).toBeInTheDocument();
    expect(screen.getByText('BL-CHAT-004')).toBeInTheDocument();
    expect(screen.getByText('BL-CHAT-F01')).toBeInTheDocument();
    expect(screen.getByText('BL-CHAT-F04')).toBeInTheDocument();

    const commitLink = screen.getByRole('link', { name: /cb55714/ });
    expect(commitLink).toHaveAttribute(
      'href',
      'https://github.com/Nessandro/stellarcrew-v2-private/commit/cb55714',
    );

    const story = screen.getByText(/MessageComposer form/i);
    expect(story).toHaveClass('line-through');
  });

  it('should render non-milestone threads as planned with BL-DX and BL-DOC tasks', async () => {
    render(<RoadmapPanel />);

    const header = screen.getByText(/Non-milestone threads — DX and documentation/i);
    await userEvent.click(header);

    expect(screen.getByText('BL-DX-001')).toBeInTheDocument();
    expect(screen.getByText('BL-DOC-001')).toBeInTheDocument();

    const story = screen.getByText(/instead of ad-hoc fakes/i);
    expect(story).not.toHaveClass('line-through');
    expect(screen.queryByRole('link', { name: /BL-(DX|DOC)-/i })).not.toBeInTheDocument();
  });
});
