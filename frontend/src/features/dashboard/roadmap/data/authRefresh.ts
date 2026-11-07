import type { RoadmapMilestone } from '../types';

export const AUTH_REFRESH_MILESTONE: RoadmapMilestone = {
  key: 'authRefresh',
  status: 'planned',
  tasks: [
    {
      id: 'BL-AUTH-004',
      description:
        'Short-lived access tokens (15 min) + HttpOnly refresh cookie (14 d) with rotation on every refresh.',
      status: 'planned',
    },
    {
      id: 'BL-AUTH-005',
      description:
        'JTI denylist in Redis for forced revocation (logout-all, password change) with background cleanup.',
      status: 'planned',
    },
  ],
};
