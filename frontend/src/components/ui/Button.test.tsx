import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { Button } from './Button';

describe('Button', () => {
  it('should render primary variant classes by default', () => {
    render(<Button>Create</Button>);

    const button = screen.getByRole('button', { name: /create/i });
    expect(button).toHaveClass('bg-sc-primary');
    expect(button).toHaveClass('text-sc-primary-foreground');
  });

  it('should render secondary variant with cancel-colored text', () => {
    render(<Button variant="secondary">Cancel</Button>);

    const button = screen.getByRole('button', { name: /cancel/i });
    expect(button).toHaveClass('text-sc-text-cancel');
    expect(button).not.toHaveClass('bg-sc-primary');
  });

  it('should render destructive variant with danger background', () => {
    render(<Button variant="destructive">Delete</Button>);

    const button = screen.getByRole('button', { name: /delete/i });
    expect(button).toHaveClass('bg-sc-danger-muted');
  });

  it('should render trailing icon next to the label', () => {
    render(<Button trailingIcon={<span data-testid="trailing">→</span>}>Continue</Button>);

    expect(screen.getByTestId('trailing')).toBeInTheDocument();
  });

  it('should invoke onClick when the user clicks the button', async () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>Save</Button>);

    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('should default type to button to prevent accidental form submissions', () => {
    render(<Button>Save</Button>);

    expect(screen.getByRole('button', { name: /save/i })).toHaveAttribute('type', 'button');
  });
});
