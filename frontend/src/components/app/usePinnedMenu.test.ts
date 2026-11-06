import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { PINNED_MENU_LIMIT, PINNED_STORAGE_KEY, usePinnedMenu } from './usePinnedMenu';

describe('usePinnedMenu', () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  afterEach(() => {
    window.localStorage.clear();
  });

  it('should start with empty pinned list when storage is empty', () => {
    const { result } = renderHook(() => usePinnedMenu());

    expect(result.current.pinned).toEqual([]);
    expect(result.current.canPinMore).toBe(true);
    expect(result.current.isPinned('dashboard')).toBe(false);
  });

  it('should add an id to pinned list when toggled on', () => {
    const { result } = renderHook(() => usePinnedMenu());

    act(() => {
      result.current.toggle('dashboard');
    });

    expect(result.current.pinned).toEqual(['dashboard']);
    expect(result.current.isPinned('dashboard')).toBe(true);
  });

  it('should remove an id from pinned list when toggled off', () => {
    const { result } = renderHook(() => usePinnedMenu());

    act(() => {
      result.current.toggle('dashboard');
    });
    act(() => {
      result.current.toggle('dashboard');
    });

    expect(result.current.pinned).toEqual([]);
    expect(result.current.isPinned('dashboard')).toBe(false);
  });

  it('should refuse to add a new pin when the limit is reached', () => {
    const { result } = renderHook(() => usePinnedMenu());
    const ids = ['a', 'b', 'c', 'd', 'e'];

    act(() => {
      ids.forEach((id) => result.current.toggle(id));
    });
    act(() => {
      result.current.toggle('overflow');
    });

    expect(result.current.pinned).toEqual(ids);
    expect(result.current.pinned).toHaveLength(PINNED_MENU_LIMIT);
    expect(result.current.canPinMore).toBe(false);
    expect(result.current.isPinned('overflow')).toBe(false);
  });

  it('should still allow unpinning when the limit is reached', () => {
    const { result } = renderHook(() => usePinnedMenu());
    const ids = ['a', 'b', 'c', 'd', 'e'];

    act(() => {
      ids.forEach((id) => result.current.toggle(id));
    });
    act(() => {
      result.current.toggle('c');
    });

    expect(result.current.pinned).toEqual(['a', 'b', 'd', 'e']);
    expect(result.current.canPinMore).toBe(true);
  });

  it('should persist pinned list to localStorage', () => {
    const { result } = renderHook(() => usePinnedMenu());

    act(() => {
      result.current.toggle('dashboard');
      result.current.toggle('workspaces.list');
    });

    const raw = window.localStorage.getItem(PINNED_STORAGE_KEY);
    expect(raw).not.toBeNull();
    expect(JSON.parse(raw as string)).toEqual(['dashboard', 'workspaces.list']);
  });

  it('should hydrate pinned list from localStorage on mount', () => {
    window.localStorage.setItem(
      PINNED_STORAGE_KEY,
      JSON.stringify(['dashboard', 'workspaces.list']),
    );

    const { result } = renderHook(() => usePinnedMenu());

    expect(result.current.pinned).toEqual(['dashboard', 'workspaces.list']);
    expect(result.current.isPinned('dashboard')).toBe(true);
  });

  it('should fall back to empty list when stored value is malformed', () => {
    window.localStorage.setItem(PINNED_STORAGE_KEY, '{"not":"an-array"}');

    const { result } = renderHook(() => usePinnedMenu());

    expect(result.current.pinned).toEqual([]);
  });
});
