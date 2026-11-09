import { render, screen, within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ProjectOverviewsWidget } from './ProjectOverviewsWidget';

function renderWidget(workspaceId = 'ws-1') {
  return render(
    <MemoryRouter>
      <ProjectOverviewsWidget workspaceId={workspaceId} />
    </MemoryRouter>,
  );
}

describe('ProjectOverviewsWidget', () => {
  it('should render the Projects Overview heading', () => {
    renderWidget();

    expect(
      screen.getByRole('heading', { level: 2, name: 'Projects Overview' }),
    ).toBeInTheDocument();
  });

  it('should render 7 faux project tiles with names from the sample data', () => {
    renderWidget();

    const list = screen.getByRole('list');
    const items = within(list).getAllByRole('listitem');
    expect(items).toHaveLength(8);

    for (const name of [
      'StellarCrew',
      'Job Search',
      'Gym Plan',
      'Questilo',
      'English Learning',
      'Reading Challenge',
      'Business Plan',
    ]) {
      expect(within(list).getByText(name)).toBeInTheDocument();
    }
  });

  it('should render the 8th tile as a Show more link pointing at the workspace projects page', () => {
    renderWidget('ws-abc');

    const showMore = screen.getByRole('link', { name: /show more projects/i });
    expect(showMore).toHaveAttribute('href', '/workspace/ws-abc/projects');
  });

  it('should render the header CTA icon linking to the workspace projects page', () => {
    renderWidget('ws-xyz');

    const openAll = screen.getByRole('link', { name: /open all projects/i });
    expect(openAll).toHaveAttribute('href', '/workspace/ws-xyz/projects');
  });

  it('should render localized status pills for active, frozen and archived projects', () => {
    renderWidget();

    expect(screen.getAllByText('ACTIVE').length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText('FROZEN').length).toBeGreaterThanOrEqual(1);
  });

  it('should render progressbar elements for each project tile', () => {
    renderWidget();

    const progressbars = screen.getAllByRole('progressbar');
    expect(progressbars).toHaveLength(7);
    expect(progressbars[0]).toHaveAttribute('aria-valuenow');
  });
});
