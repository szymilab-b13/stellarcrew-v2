import type { RoadmapMilestone } from '../types';

export const AUTH_MILESTONE: RoadmapMilestone = {
  key: 'auth',
  status: 'done',
  tasks: [
    {
      id: 'BL-AUTH-001',
      description:
        'lexik/jwt-authentication-bundle (RS256, 1 h access TTL), SecurityUser adapter, POST /api/auth/login + GET /api/auth/me + POST /api/auth/logout (ADR-BE-004).',
      status: 'done',
      commit: 'ac605d5',
    },
    {
      id: 'BL-AUTH-002',
      description:
        'Frontend /login page, Bearer token storage, apiClient attaches Authorization header on every request.',
      status: 'done',
      commit: '83708ef',
    },
    {
      id: 'BL-AUTH-003',
      description: 'Home-page route guard, topbar greeting with current user and Logout action.',
      status: 'done',
      commit: 'c05505c',
    },
  ],
};
