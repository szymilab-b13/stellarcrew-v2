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
});
