import { Building2, Sparkles } from 'lucide-react';

import type { WorkspaceState } from '../../types';

interface Agent {
  slug: string;
  name: string;
  role: string;
  colorVar: string;
}

const AGENTS: readonly Agent[] = [
  { slug: 'maja', name: 'Maja', role: 'Project manager', colorVar: 'var(--color-sc-agent-maja)' },
  { slug: 'viktor', name: 'Viktor', role: 'Tech lead', colorVar: 'var(--color-sc-agent-viktor)' },
  { slug: 'luna', name: 'Luna', role: 'Researcher', colorVar: 'var(--color-sc-agent-luna)' },
  { slug: 'kacper', name: 'Kacper', role: 'Engineer', colorVar: 'var(--color-sc-agent-kacper)' },
  { slug: 'patryk', name: 'Patryk', role: 'Analyst', colorVar: 'var(--color-sc-agent-patryk)' },
  {
    slug: 'hefajstos',
    name: 'Hefajstos',
    role: 'Autonomous builder',
    colorVar: 'var(--color-sc-agent-maja)',
  },
];

interface WorkspaceStepProps {
  workspace: WorkspaceState;
  selectedAgentSlugs: readonly string[];
  onWorkspaceChange(next: WorkspaceState): void;
  onAgentsChange(next: string[]): void;
  errors: Partial<Record<'name', string>>;
}

export function WorkspaceStep({
  workspace,
  selectedAgentSlugs,
  onWorkspaceChange,
  onAgentsChange,
  errors,
}: WorkspaceStepProps) {
  function toggleAgent(slug: string) {
    const next = selectedAgentSlugs.includes(slug)
      ? selectedAgentSlugs.filter((item) => item !== slug)
      : [...selectedAgentSlugs, slug];
    onAgentsChange(next);
  }

  return (
    <div className="flex flex-col gap-6">
      <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
        <div className="flex items-center gap-3 border-b border-sc-border pb-5">
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
            <Building2 size={18} aria-hidden />
          </span>
          <div>
            <h3 className="text-base font-semibold text-sc-text">First workspace</h3>
            <p className="text-xs text-sc-text-muted">
              Workspaces group a project, its data and its agents. You can always add more later.
            </p>
          </div>
        </div>

        <div className="mt-6 flex flex-col gap-1.5">
          <label htmlFor="workspace-name" className="text-xs font-medium text-sc-text">
            Workspace name <span className="ml-1 text-red-500">*</span>
          </label>
          <input
            id="workspace-name"
            type="text"
            placeholder="e.g. Acme HQ"
            aria-invalid={Boolean(errors.name)}
            value={workspace.name}
            onChange={(event) => onWorkspaceChange({ name: event.target.value })}
            className="w-full rounded-md border border-sc-border bg-sc-bg px-3 py-2 text-sm text-sc-text outline-none transition-colors focus:border-sc-text"
          />
          {errors.name ? (
            <p role="alert" className="text-xs text-red-600">
              {errors.name}
            </p>
          ) : (
            <p className="text-xs text-sc-text-muted">
              You can rename it at any time from the workspace settings.
            </p>
          )}
        </div>
      </section>

      <section className="rounded-lg border border-sc-border bg-sc-bg-card p-6 shadow-sm">
        <div className="flex items-center gap-3 border-b border-sc-border pb-5">
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-sc-border bg-sc-bg-secondary text-sc-text">
            <Sparkles size={18} aria-hidden />
          </span>
          <div>
            <h3 className="text-base font-semibold text-sc-text">Agents in this workspace</h3>
            <p className="text-xs text-sc-text-muted">
              Pick who joins first. More agents and custom roles can be added later — this is a
              placeholder, nothing is provisioned yet.
            </p>
          </div>
        </div>

        <ul className="mt-6 grid gap-3 sm:grid-cols-2">
          {AGENTS.map((agent) => {
            const isSelected = selectedAgentSlugs.includes(agent.slug);
            return (
              <li key={agent.slug}>
                <label
                  className={`flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2.5 transition-colors ${
                    isSelected
                      ? 'border-sc-text bg-sc-bg-secondary'
                      : 'border-sc-border bg-sc-bg-card hover:bg-sc-bg-secondary'
                  }`}
                >
                  <input
                    type="checkbox"
                    className="sr-only"
                    checked={isSelected}
                    onChange={() => toggleAgent(agent.slug)}
                    aria-label={`Include ${agent.name}`}
                  />
                  <span
                    aria-hidden
                    className="inline-flex h-9 w-9 flex-none items-center justify-center rounded-full text-sm font-semibold text-white"
                    style={{ backgroundColor: agent.colorVar }}
                  >
                    {agent.name.charAt(0)}
                  </span>
                  <span className="flex flex-col">
                    <span className="text-sm font-medium text-sc-text">{agent.name}</span>
                    <span className="text-xs text-sc-text-muted">{agent.role}</span>
                  </span>
                  <span
                    className={`ml-auto h-5 w-5 flex-none rounded-full border ${isSelected ? 'border-sc-text bg-sc-text' : 'border-sc-border bg-sc-bg'}`}
                  />
                </label>
              </li>
            );
          })}
        </ul>
      </section>
    </div>
  );
}
