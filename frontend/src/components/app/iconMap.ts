import { Building2, Circle, LayoutDashboard, Settings, Users, type LucideIcon } from 'lucide-react';

const ICON_MAP: Record<string, LucideIcon> = {
  LayoutDashboard,
  Building2,
  Settings,
  Users,
};

export function resolveIcon(name: string): LucideIcon {
  return ICON_MAP[name] ?? Circle;
}
