import type { RoadmapMilestone } from '../types';

export const WORKER_AUTH_MILESTONE: RoadmapMilestone = {
  key: 'workerAuth',
  status: 'done',
  note: 'Worker pairing + JWT auth pipeline: pair endpoint → access token (Lexik JWT, 15min TTL) + refresh token (90-day, one-use rotation, sha256-hashed) → fingerprint mismatch detection + accept endpoint + history (JSONB). Legacy X-Worker-* header auth removed. 4 PRs merged (PR #26 AUTH-FINGERPRINT 803def7, PR #27 AUTH-JWT 1c4566a, PR #28 WORKER-AUTH rest 2c3d428, PR #29 follow-ups 677a0fa). Backend 884/884 tests.',
  tasks: [
    { id: 'BL-AUTH-FP-001', status: 'done', commit: '803def7' },
    { id: 'BL-AUTH-JWT-001', status: 'done', commit: '1c4566a' },
    { id: 'BL-WA-005a', status: 'done', commit: '00f2967' },
    { id: 'BL-WA-005b', status: 'done', commit: 'bb50f7f' },
    { id: 'BL-WA-005c', status: 'done', commit: '48e61ce' },
    { id: 'BL-WA-006a', status: 'done', commit: '70f293d' },
    { id: 'BL-WA-007a', status: 'done', commit: 'd8f7ba2' },
    { id: 'BL-WA-007c', status: 'done', commit: '051e528' },
    { id: 'BL-WA-008a', status: 'done', commit: 'fa68850' },
    { id: 'BL-WA-009a', status: 'done', commit: '14fca4d' },
    { id: 'BL-WA-009b', status: 'done', commit: '094a6d9' },
  ],
};
