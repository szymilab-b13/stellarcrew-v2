import type { RoadmapMilestone } from '../types';

export const AGENTS_UI_MILESTONE: RoadmapMilestone = {
  key: 'agentsUi',
  status: 'done',
  note: 'Frontend UI dla modułu Agents, konsumujące backend AGENTS-CRUD + AGENTS-TEMPLATES. Decyzje Szefa 2026-11-15: avatary kolor+inicjały, Overview rozszerzony (metadata + activity stub), Delete redirect z banerem+Undo (jak PROJECTS BL-PROJ-015), sidebar aktywacja w BL-AGUI-002. 8/8 BL done, branch feat/agents-ui czeka na MR do main.',
  tasks: [
    { id: 'BL-AGUI-001', status: 'done', commit: '371faa7' },
    { id: 'BL-AGUI-002', status: 'done', commit: '6a799c1' },
    { id: 'BL-AGUI-003', status: 'done', commit: '10ba71b' },
    { id: 'BL-AGUI-004', status: 'done', commit: 'c1b915a' },
    { id: 'BL-AGUI-005', status: 'done', commit: '87fc2c7' },
    { id: 'BL-AGUI-006', status: 'done', commit: '0eb022f' },
    { id: 'BL-AGUI-007', status: 'done', commit: '929f9cf' },
    { id: 'BL-AGUI-008', status: 'done', commit: 'e80cba0' },
  ],
};
