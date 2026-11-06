import { useCallback, useEffect, useState } from 'react';

const COLLAPSED_STORAGE_KEY = 'sc-sidebar-collapsed';
const SECTIONS_STORAGE_KEY = 'sc-sidebar-collapsed-sections';

function readBoolean(key: string, fallback: boolean): boolean {
  if (typeof window === 'undefined') return fallback;
  try {
    const raw = window.localStorage.getItem(key);
    if (raw === 'true') return true;
    if (raw === 'false') return false;
    return fallback;
  } catch {
    return fallback;
  }
}

function readSet(key: string): Set<string> {
  if (typeof window === 'undefined') return new Set();
  try {
    const raw = window.localStorage.getItem(key);
    if (!raw) return new Set();
    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) return new Set();
    return new Set(parsed.filter((item): item is string => typeof item === 'string'));
  } catch {
    return new Set();
  }
}

export function useSidebarCollapsed(): [boolean, (next: boolean) => void] {
  const [collapsed, setCollapsed] = useState(() => readBoolean(COLLAPSED_STORAGE_KEY, false));

  useEffect(() => {
    try {
      window.localStorage.setItem(COLLAPSED_STORAGE_KEY, String(collapsed));
    } catch {
      // ignore
    }
  }, [collapsed]);

  return [collapsed, setCollapsed];
}

export function useCollapsedSections(): {
  isCollapsed: (sectionId: string) => boolean;
  toggle: (sectionId: string) => void;
} {
  const [set, setSet] = useState<Set<string>>(() => readSet(SECTIONS_STORAGE_KEY));

  useEffect(() => {
    try {
      window.localStorage.setItem(SECTIONS_STORAGE_KEY, JSON.stringify([...set]));
    } catch {
      // ignore
    }
  }, [set]);

  const toggle = useCallback((sectionId: string) => {
    setSet((previous) => {
      const next = new Set(previous);
      if (next.has(sectionId)) {
        next.delete(sectionId);
      } else {
        next.add(sectionId);
      }
      return next;
    });
  }, []);

  const isCollapsed = useCallback((sectionId: string) => set.has(sectionId), [set]);

  return { isCollapsed, toggle };
}
