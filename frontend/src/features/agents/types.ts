export interface Agent {
  id: string;
  organizationId: string;
  ownerUserId: string;
  sourceTemplateId: string | null;
  slug: string;
  name: string;
  role: string;
  roleDescription: string;
  expertise: string;
  workflow: string;
  style: string;
  outputFormat: string;
  isActive: boolean;
  color: string;
  createdAt: string;
  updatedAt: string;
}

export interface AgentTemplate {
  id: string;
  slug: string;
  name: string;
  role: string;
  roleDescription: string;
  expertise: string;
  workflow: string;
  style: string;
  outputFormat: string;
  createdAt: string;
  updatedAt: string;
}
