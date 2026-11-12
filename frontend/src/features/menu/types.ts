export interface MenuItem {
  id: string;
  labelKey: string;
  icon: string;
  path: string;
  order: number;
  disabledMilestoneKey?: string | null;
}

export interface MenuSection {
  id: string;
  labelKey: string;
  order: number;
  items: MenuItem[];
}

interface MenuApiPayload {
  sections: MenuSection[];
}

export function parseMenu(payload: MenuApiPayload): MenuSection[] {
  return payload.sections;
}
