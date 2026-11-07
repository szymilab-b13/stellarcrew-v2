export type MilestoneStatus = 'done' | 'inProgress' | 'planned' | 'tbd';

export interface RoadmapTask {
  id: string;
  description: string;
  status: MilestoneStatus;
  commit?: string;
}

export interface RoadmapMilestone {
  key: string;
  status: MilestoneStatus;
  note?: string;
  tasks: RoadmapTask[];
}
