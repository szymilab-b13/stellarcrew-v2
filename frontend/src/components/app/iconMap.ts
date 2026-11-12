import {
  Bot,
  Building2,
  Circle,
  LayoutDashboard,
  MessageSquare,
  Settings,
  Users,
  type LucideIcon,
} from 'lucide-react';

const ICON_MAP: Record<string, LucideIcon> = {
  LayoutDashboard,
  Building2,
  MessageSquare,
  Bot,
  Settings,
  Users,
};

export function resolveIcon(name: string): LucideIcon {
  return ICON_MAP[name] ?? Circle;
}
