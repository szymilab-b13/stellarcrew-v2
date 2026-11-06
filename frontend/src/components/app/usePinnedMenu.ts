import { useCallback, useEffect, useState } from 'react';

export const PINNED_STORAGE_KEY = 'sc-sidebar-pinned';
export const PINNED_MENU_LIMIT = 5;

function readPinned(): string[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = window.localStorage.getItem(PINNED_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) return [];
    return parsed
      .filter((item): item is string => typeof item === 'string')
      .slice(0, PINNED_MENU_LIMIT);
  } catch {
    return [];
  }
}

export interface PinnedMenuApi {
  pinned: string[];
  canPinMore: boolean;
  isPinned: (id: string) => boolean;
  toggle: (id: string) => void;
}

export function usePinnedMenu(): PinnedMenuApi {
  const [pinned, setPinned] = useState<string[]>(() => readPinned());

  useEffect(() => {
    try {
      window.localStorage.setItem(PINNED_STORAGE_KEY, JSON.stringify(pinned));
    } catch {
      // ignore
    }
  }, [pinned]);

  const toggle = useCallback((id: string) => {
    setPinned((previous) => {
      if (previous.includes(id)) {
        return previous.filter((existing) => existing !== id);
      }
      if (previous.length >= PINNED_MENU_LIMIT) {
        return previous;
      }
      return [...previous, id];
    });
  }, []);

  const isPinned = useCallback((id: string) => pinned.includes(id), [pinned]);

  return {
    pinned,
    canPinMore: pinned.length < PINNED_MENU_LIMIT,
    isPinned,
    toggle,
  };
}
