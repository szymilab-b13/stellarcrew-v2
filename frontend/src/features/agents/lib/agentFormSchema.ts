import { z } from 'zod';

import { AGENT_SLUG_REGEX } from './agentSlug';

export const agentFormSchema = z.object({
  name: z.string().trim().min(1, 'name.required').max(80, 'name.tooLong'),
  role: z.string().trim().min(1, 'role.required').max(120, 'role.tooLong'),
  slug: z
    .string()
    .trim()
    .min(1, 'slug.required')
    .max(80, 'slug.tooLong')
    .regex(AGENT_SLUG_REGEX, 'slug.invalid'),
  roleDescription: z
    .string()
    .trim()
    .min(1, 'roleDescription.required')
    .max(500, 'roleDescription.tooLong'),
  expertise: z.string().trim().min(1, 'expertise.required').max(2000, 'expertise.tooLong'),
  workflow: z.string().trim().min(1, 'workflow.required').max(2000, 'workflow.tooLong'),
  style: z.string().trim().min(1, 'style.required').max(2000, 'style.tooLong'),
  outputFormat: z.string().trim().min(1, 'outputFormat.required').max(2000, 'outputFormat.tooLong'),
});

export type AgentFormValues = z.infer<typeof agentFormSchema>;

export const EMPTY_AGENT_FORM_VALUES: AgentFormValues = {
  name: '',
  role: '',
  slug: '',
  roleDescription: '',
  expertise: '',
  workflow: '',
  style: '',
  outputFormat: '',
};
