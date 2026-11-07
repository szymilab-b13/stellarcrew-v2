import { useEffect, useMemo } from 'react';
import { create } from 'zustand';

export interface BreadcrumbSegment {
  label: string;
  to?: string;
}

interface BreadcrumbsState {
  segments: BreadcrumbSegment[];
  setSegments(segments: BreadcrumbSegment[]): void;
  clear(): void;
}

const useBreadcrumbsStore = create<BreadcrumbsState>((set) => ({
  segments: [],
  setSegments: (segments) => set({ segments }),
  clear: () => set({ segments: [] }),
}));

export function useBreadcrumbs(): BreadcrumbSegment[] {
  return useBreadcrumbsStore((state) => state.segments);
}

export function usePageBreadcrumbs(segments: BreadcrumbSegment[]): void {
  const serialized = useMemo(() => JSON.stringify(segments), [segments]);
  useEffect(() => {
    const next: BreadcrumbSegment[] = JSON.parse(serialized);
    useBreadcrumbsStore.getState().setSegments(next);
    return () => {
      useBreadcrumbsStore.getState().clear();
    };
  }, [serialized]);
}
