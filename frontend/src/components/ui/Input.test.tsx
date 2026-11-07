import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { Input } from './Input';

describe('Input', () => {
  it('should apply primary border classes when valid', () => {
    render(<Input aria-label="name" />);

    const input = screen.getByLabelText(/name/i);
    expect(input).toHaveClass('border-sc-border');
    expect(input).toHaveClass('focus:border-sc-primary');
    expect(input).not.toHaveAttribute('aria-invalid');
  });

  it('should apply danger border classes and aria-invalid when invalid', () => {
    render(<Input aria-label="name" invalid />);

    const input = screen.getByLabelText(/name/i);
    expect(input).toHaveClass('border-sc-danger');
    expect(input).toHaveClass('focus:border-sc-danger');
    expect(input).toHaveAttribute('aria-invalid', 'true');
  });
});
