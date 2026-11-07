import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { CURRENT_WORKSPACE_STORAGE_KEY, useCurrentWorkspace } from './useCurrentWorkspace';

describe('useCurrentWorkspace', () => {
  beforeEach(() => {
    window.localStorage.clear();
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
  });

  afterEach(() => {
    window.localStorage.clear();
    useCurrentWorkspace.setState({ currentWorkspaceId: null });
  });

  it('should startWithNullIdWhenStorageIsEmpty', () => {
    const { result } = renderHook(() => useCurrentWorkspace());

    expect(result.current.currentWorkspaceId).toBeNull();
  });

  it('should storeIdWhenSetCurrentWorkspaceIdIsCalled', () => {
    const { result } = renderHook(() => useCurrentWorkspace());

    act(() => {
      result.current.setCurrentWorkspaceId('abc-123');
    });

    expect(result.current.currentWorkspaceId).toBe('abc-123');
  });

  it('should persistIdToLocalStorageWhenSet', () => {
    const { result } = renderHook(() => useCurrentWorkspace());

    act(() => {
      result.current.setCurrentWorkspaceId('abc-123');
    });

    const raw = window.localStorage.getItem(CURRENT_WORKSPACE_STORAGE_KEY);
    expect(raw).not.toBeNull();
    const parsed = JSON.parse(raw as string) as { state: { currentWorkspaceId: string | null } };
    expect(parsed.state.currentWorkspaceId).toBe('abc-123');
  });

  it('should clearIdWhenResetCurrentWorkspaceIsCalled', () => {
    const { result } = renderHook(() => useCurrentWorkspace());

    act(() => {
      result.current.setCurrentWorkspaceId('abc-123');
    });
    act(() => {
      result.current.resetCurrentWorkspace();
    });

    expect(result.current.currentWorkspaceId).toBeNull();
  });

  it('should hydrateIdFromLocalStorageOnInit', async () => {
    window.localStorage.setItem(
      CURRENT_WORKSPACE_STORAGE_KEY,
      JSON.stringify({ state: { currentWorkspaceId: 'hydrated-id' }, version: 0 }),
    );

    await act(async () => {
      await useCurrentWorkspace.persist.rehydrate();
    });

    const { result } = renderHook(() => useCurrentWorkspace());

    expect(result.current.currentWorkspaceId).toBe('hydrated-id');
  });
});
