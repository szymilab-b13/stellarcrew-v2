import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { ProjectContextSection } from './ProjectContextSection';

function Wrapper({ children, initialPath }: { children: ReactNode; initialPath: string }) {
  return (
    <MemoryRouter initialEntries={[initialPath]}>
      <Routes>
        <Route path="/workspace/:wsId/project/:projId/*" element={children} />
        <Route path="/*" element={children} />
      </Routes>
    </MemoryRouter>
  );
}

function renderAt(initialPath: string, props: { collapsed?: boolean } = {}) {
  return render(<ProjectContextSection collapsed={props.collapsed ?? false} />, {
    wrapper: ({ children }) => <Wrapper initialPath={initialPath}>{children}</Wrapper>,
  });
}

describe('ProjectContextSection', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  afterEach(() => {
    window.localStorage.clear();
  });

  it('should render nothing when route is outside a project', () => {
    const { container } = renderAt('/workspace/ws-abc/overview');

    expect(container).toBeEmptyDOMElement();
  });

  it('should render nothing when route is outside any workspace', () => {
    const { container } = renderAt('/dashboard');

    expect(container).toBeEmptyDOMElement();
  });

  it('should render Overview and Settings as active links scoped to the current wsId and projId', () => {
    renderAt('/workspace/ws-abc/project/proj-xyz/overview');

    const overviewLink = screen.getByRole('link', { name: 'Overview' });
    const settingsLink = screen.getByRole('link', { name: 'Settings' });

    expect(overviewLink).toHaveAttribute('href', '/workspace/ws-abc/project/proj-xyz/overview');
    expect(settingsLink).toHaveAttribute('href', '/workspace/ws-abc/project/proj-xyz/settings');
    expect(screen.getAllByRole('link')).toHaveLength(2);
  });

  it('should render Agents, Members, Scheduler, Skills, Flow and Tasks as disabled placeholders with a milestone tooltip', () => {
    renderAt('/workspace/ws-abc/project/proj-xyz/overview');

    const list = screen.getByRole('list');
    const placeholderTexts = ['Agents', 'Members', 'Scheduler', 'Skills', 'Flow', 'Tasks'];

    for (const label of placeholderTexts) {
      expect(within(list).queryByRole('link', { name: label })).toBeNull();
      const placeholder = within(list).getByText(label);
      const row = placeholder.closest('span[aria-disabled="true"]');
      expect(row).not.toBeNull();
      expect(row).toHaveAttribute('title', expect.stringMatching(/coming after/i));
    }
  });

  it('should render a collapsible section header labelled Project', async () => {
    renderAt('/workspace/ws-1/project/proj-1/overview');

    const header = screen.getByRole('button', { name: /project/i });

    expect(header).toHaveAttribute('aria-expanded', 'true');
    expect(screen.getByRole('link', { name: 'Overview' })).toBeInTheDocument();

    await userEvent.click(header);

    expect(header).toHaveAttribute('aria-expanded', 'false');
    expect(screen.queryByRole('link', { name: 'Overview' })).not.toBeInTheDocument();
  });

  it('should hide the section header and keep the Overview icon visible when the sidebar is collapsed', () => {
    renderAt('/workspace/ws-1/project/proj-1/overview', { collapsed: true });

    expect(screen.queryByRole('button', { name: /project/i })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Overview' })).toBeInTheDocument();
  });

  it('should stay visible on nested project sub-routes', () => {
    renderAt('/workspace/ws-1/project/proj-1/settings');

    expect(screen.getByRole('link', { name: 'Overview' })).toHaveAttribute(
      'href',
      '/workspace/ws-1/project/proj-1/overview',
    );
  });
});
