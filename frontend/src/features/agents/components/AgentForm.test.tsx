import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';

import '@/i18n/i18n';

import { EMPTY_AGENT_FORM_VALUES, type AgentFormValues } from '../lib/agentFormSchema';
import { AgentForm } from './AgentForm';

const filledValues: AgentFormValues = {
  name: 'Maja',
  role: 'Project Manager',
  slug: 'maja',
  roleDescription: 'Keeps the roadmap on track.',
  expertise: 'Coordination and planning',
  workflow: 'Daily check-ins and briefings',
  style: 'Calm and direct',
  outputFormat: 'TL;DR first, bullets after',
};

interface HarnessProps {
  defaultValues?: AgentFormValues;
  onSubmit?: (values: AgentFormValues) => void;
  isSubmitting?: boolean;
  serverError?: string | null;
  slugLocked?: boolean;
}

function renderForm({
  defaultValues = EMPTY_AGENT_FORM_VALUES,
  onSubmit = vi.fn(),
  isSubmitting = false,
  serverError = null,
  slugLocked = false,
}: HarnessProps = {}) {
  return render(
    <AgentForm
      defaultValues={defaultValues}
      onSubmit={onSubmit}
      isSubmitting={isSubmitting}
      serverError={serverError}
      submitLabel="Create agent"
      submittingLabel="Creating…"
      slugLocked={slugLocked}
    />,
  );
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe('AgentForm', () => {
  it('should render all eight fields (name, role, slug + five sections) with labels', () => {
    renderForm();

    expect(screen.getByLabelText('Name')).toBeInTheDocument();
    expect(screen.getByLabelText('Role')).toBeInTheDocument();
    expect(screen.getByLabelText('Slug')).toBeInTheDocument();
    expect(screen.getByLabelText('Role description')).toBeInTheDocument();
    expect(screen.getByLabelText('Expertise')).toBeInTheDocument();
    expect(screen.getByLabelText('Workflow')).toBeInTheDocument();
    expect(screen.getByLabelText('Style')).toBeInTheDocument();
    expect(screen.getByLabelText('Output format')).toBeInTheDocument();
  });

  it('should prefill all fields from defaultValues', () => {
    renderForm({ defaultValues: filledValues });

    expect(screen.getByLabelText('Name')).toHaveValue('Maja');
    expect(screen.getByLabelText('Role')).toHaveValue('Project Manager');
    expect(screen.getByLabelText('Slug')).toHaveValue('maja');
    expect(screen.getByLabelText('Role description')).toHaveValue('Keeps the roadmap on track.');
    expect(screen.getByLabelText('Expertise')).toHaveValue('Coordination and planning');
    expect(screen.getByLabelText('Workflow')).toHaveValue('Daily check-ins and briefings');
    expect(screen.getByLabelText('Style')).toHaveValue('Calm and direct');
    expect(screen.getByLabelText('Output format')).toHaveValue('TL;DR first, bullets after');
  });

  it('should show required errors and not call onSubmit when all fields are empty', async () => {
    const onSubmit = vi.fn();
    renderForm({ onSubmit });

    const user = userEvent.setup();
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(onSubmit).not.toHaveBeenCalled();
    expect(await screen.findByText('Name is required.')).toBeInTheDocument();
    expect(screen.getByText('Role is required.')).toBeInTheDocument();
    expect(screen.getByText('Slug is required.')).toBeInTheDocument();
    expect(screen.getByText('Role description is required.')).toBeInTheDocument();
    expect(screen.getByText('Expertise is required.')).toBeInTheDocument();
    expect(screen.getByText('Workflow is required.')).toBeInTheDocument();
    expect(screen.getByText('Style is required.')).toBeInTheDocument();
    expect(screen.getByText('Output format is required.')).toBeInTheDocument();
  });

  it('should show a slug format error when slug contains uppercase or spaces', async () => {
    const onSubmit = vi.fn();
    renderForm({ defaultValues: { ...filledValues, slug: 'Maja Agent' }, onSubmit });

    const user = userEvent.setup();
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(
      await screen.findByText('Slug must contain lowercase letters, digits and dashes only.'),
    ).toBeInTheDocument();
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it('should call onSubmit with the full payload when the form is valid', async () => {
    const onSubmit = vi.fn();
    renderForm({ defaultValues: filledValues, onSubmit });

    const user = userEvent.setup();
    await user.click(screen.getByRole('button', { name: 'Create agent' }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0]?.[0]).toEqual(filledValues);
  });

  it('should render the submitting label and disable the submit button when isSubmitting', () => {
    renderForm({ defaultValues: filledValues, isSubmitting: true });

    const submit = screen.getByRole('button', { name: 'Creating…' });
    expect(submit).toBeDisabled();
  });

  it('should render a server error alert when serverError prop is provided', () => {
    renderForm({ defaultValues: filledValues, serverError: 'This slug is already taken.' });

    expect(screen.getByRole('alert')).toHaveTextContent('This slug is already taken.');
  });

  it('should render the slug input as readOnly and show the locked hint when slugLocked is true', () => {
    renderForm({ defaultValues: filledValues, slugLocked: true });

    const slug = screen.getByLabelText('Slug');
    expect(slug).toHaveAttribute('readonly');
    expect(
      screen.getByText('Slug is fixed once the agent is created and cannot be changed later.'),
    ).toBeInTheDocument();
  });
});
