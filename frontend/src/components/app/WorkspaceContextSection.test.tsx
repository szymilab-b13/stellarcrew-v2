import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import '@/i18n/i18n';

import { WorkspaceContextSection } from './WorkspaceContextSection';

function Wrapper({ children, initialPath }: { children: ReactNode; initialPath: string }) {
  return (
    <MemoryRouter initialEntries={[initialPath]}>
      <Routes>
        <Route path="/workspace/:wsId/*" element={children} />
        <Route path="/*" element={children} />
      </Routes>
    </MemoryRouter>
  );
}

function renderAt(initialPath: string, props: { collapsed?: boolean } = {}) {
  return render(<WorkspaceContextSection collapsed={props.collapsed ?? false} />, {
    wrapper: ({ children }) => <Wrapper initialPath={initialPath}>{children}</Wrapper>,
  });
}

describe('WorkspaceContextSection', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  afterEach(() => {
    window.localStorage.clear();
  });

  it('should render nothing when route is not inside a workspace', () => {
    const { container } = renderAt('/dashboard');

    expect(container).toBeEmptyDOMElement();
  });

  it('should render Overview and Members as active links scoped to the current wsId', () => {
    renderAt('/workspace/ws-abc/overview');

    const overviewLink = screen.getByRole('link', { name: 'Overview' });
    const membersLink = screen.getByRole('link', { name: 'Members' });

    expect(overviewLink).toHaveAttribute('href', '/workspace/ws-abc/overview');
    expect(membersLink).toHaveAttribute('href', '/workspace/ws-abc/settings/members');
  });

  it('should render Projects, Agents, Scheduler and Settings as disabled placeholders with a milestone tooltip', () => {
    renderAt('/workspace/ws-abc/overview');

    const list = screen.getByRole('list');
    const placeholderTexts = ['Projects', 'Agents', 'Scheduler', 'Settings'];

    for (const label of placeholderTexts) {
      expect(within(list).queryByRole('link', { name: label })).toBeNull();
      const placeholder = within(list).getByText(label);
      const row = placeholder.closest('span[aria-disabled="true"]');
      expect(row).not.toBeNull();
      expect(row).toHaveAttribute('title', expect.stringMatching(/coming after/i));
    }
  });

  it('should render a collapsible section header labelled Workspace', async () => {
    renderAt('/workspace/ws-1/overview');

    const header = screen.getByRole('button', { name: /workspace/i });

    expect(header).toHaveAttribute('aria-expanded', 'true');
    expect(screen.getByRole('link', { name: 'Overview' })).toBeInTheDocument();

    await userEvent.click(header);

    expect(header).toHaveAttribute('aria-expanded', 'false');
    expect(screen.queryByRole('link', { name: 'Overview' })).not.toBeInTheDocument();
  });

  it('should hide the section header and keep active item icons visible when sidebar is collapsed', () => {
    renderAt('/workspace/ws-1/overview', { collapsed: true });

    expect(screen.queryByRole('button', { name: /workspace/i })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Overview' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Members' })).toBeInTheDocument();
  });
});
