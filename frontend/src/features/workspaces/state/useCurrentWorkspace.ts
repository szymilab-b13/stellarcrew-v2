import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

export const CURRENT_WORKSPACE_STORAGE_KEY = 'sc-current-workspace';

interface CurrentWorkspaceState {
  currentWorkspaceId: string | null;
  setCurrentWorkspaceId: (id: string | null) => void;
  resetCurrentWorkspace: () => void;
}

export const useCurrentWorkspace = create<CurrentWorkspaceState>()(
  persist(
    (set) => ({
      currentWorkspaceId: null,
      setCurrentWorkspaceId: (id) => set({ currentWorkspaceId: id }),
      resetCurrentWorkspace: () => set({ currentWorkspaceId: null }),
    }),
    {
      name: CURRENT_WORKSPACE_STORAGE_KEY,
      storage: createJSONStorage(() => window.localStorage),
    },
  ),
);
