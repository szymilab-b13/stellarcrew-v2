import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import type { Project } from '../types';
import { ProjectTile } from './ProjectTile';

const project: Project = {
  id: '11111111-1111-4111-8111-111111111111',
  workspaceId: 'ws-1',
  name: 'Talent Bridge',
  slug: 'talent-bridge',
  description: 'HR integration layer across the group.',
  createdAt: '2026-11-03T10:00:00+00:00',
  updatedAt: '2026-11-09T10:00:00+00:00',
};

function renderTile(overrides?: Partial<Project>) {
  return render(
    <MemoryRouter>
      <ProjectTile project={{ ...project, ...overrides }} />
    </MemoryRouter>,
  );
}

describe('ProjectTile', () => {
  it('should render the project name and slug', () => {
    renderTile();

    expect(screen.getByRole('heading', { level: 2, name: 'Talent Bridge' })).toBeInTheDocument();
    expect(screen.getByText('talent-bridge')).toBeInTheDocument();
  });

  it('should render the description when present', () => {
    renderTile();

    expect(screen.getByText('HR integration layer across the group.')).toBeInTheDocument();
  });

  it('should skip the description paragraph when null', () => {
    renderTile({ description: null });

    expect(screen.queryByText(/HR integration/)).not.toBeInTheDocument();
  });

  it('should render a formatted created date', () => {
    renderTile();

    expect(screen.getByText(/Created/)).toBeInTheDocument();
  });

  it('should expose an aria-label with the project name on the card', () => {
    renderTile();

    expect(screen.getByRole('group', { name: /Talent Bridge/ })).toBeInTheDocument();
  });

  it('should link to the project overview page', () => {
    renderTile();

    const link = screen.getByRole('link', { name: /Open Talent Bridge/ });
    expect(link).toHaveAttribute(
      'href',
      '/workspace/ws-1/project/11111111-1111-4111-8111-111111111111/overview',
    );
  });
});
