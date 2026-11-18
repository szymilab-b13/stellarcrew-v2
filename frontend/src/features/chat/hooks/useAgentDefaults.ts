import { useCallback, useEffect, useState } from 'react';

import type { EngineType, Priority } from '../types';

export interface AgentDefaults {
  engineType: EngineType;
  engineId: string;
  priority: Priority;
}

export function defaultAgentDefaults(agentId: string): AgentDefaults {
  return {
    engineType: 'cli_terminal',
    engineId: agentId,
    priority: 'P2',
  };
}

function storageKey(agentId: string): string {
  return `chat.agent.defaults.${agentId}`;
}

function readFromStorage(agentId: string): AgentDefaults | null {
  if (typeof window === 'undefined') {
    return null;
  }
  try {
    const raw = window.localStorage.getItem(storageKey(agentId));
    if (raw === null) {
      return null;
    }
    const parsed = JSON.parse(raw) as Partial<AgentDefaults>;
    if (!parsed.engineType || !parsed.engineId || !parsed.priority) {
      return null;
    }
    return {
      engineType: parsed.engineType,
      engineId: parsed.engineId,
      priority: parsed.priority,
    };
  } catch {
    return null;
  }
}

export function useAgentDefaults(agentId: string): {
  defaults: AgentDefaults;
  update: (next: AgentDefaults) => void;
} {
  const [defaults, setDefaults] = useState<AgentDefaults>(
    () => readFromStorage(agentId) ?? defaultAgentDefaults(agentId),
  );

  useEffect(() => {
    setDefaults(readFromStorage(agentId) ?? defaultAgentDefaults(agentId));
  }, [agentId]);

  const update = useCallback(
    (next: AgentDefaults) => {
      setDefaults(next);
      if (typeof window !== 'undefined') {
        window.localStorage.setItem(storageKey(agentId), JSON.stringify(next));
      }
    },
    [agentId],
  );

  return { defaults, update };
}
