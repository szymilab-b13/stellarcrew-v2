import { EventEmitter } from 'node:events';
import type { Logger } from 'pino';
import { describe, expect, it, vi } from 'vitest';

import { createClaudeRunner } from './claudeRunner.js';

function silentLogger(): Logger {
  return {
    info: vi.fn(),
    warn: vi.fn(),
    error: vi.fn(),
    debug: vi.fn(),
    trace: vi.fn(),
    fatal: vi.fn(),
  } as unknown as Logger;
}

interface FakeChildOptions {
  readonly stdout?: string;
  readonly stderr?: string;
  readonly exitCode?: number;
  readonly signal?: NodeJS.Signals | null;
}

interface FakeChild {
  readonly stdout: EventEmitter;
  readonly stderr: EventEmitter;
  readonly stdin: { end: (data: string) => void };
  readonly emitter: EventEmitter;
  readonly capturedStdin: () => string;
}

function fakeChild(options: FakeChildOptions, onStdin: (data: string) => void): FakeChild {
  const stdout = new EventEmitter();
  const stderr = new EventEmitter();
  const emitter = new EventEmitter();
  let captured = '';
  const stdin = {
    end: (data: string): void => {
      captured = data;
      onStdin(captured);
      queueMicrotask(() => {
        if (options.stdout !== undefined) {
          stdout.emit('data', Buffer.from(options.stdout, 'utf8'));
        }
        if (options.stderr !== undefined) {
          stderr.emit('data', Buffer.from(options.stderr, 'utf8'));
        }
        emitter.emit('close', options.exitCode ?? 0, options.signal ?? null);
      });
    },
  };
  return { stdout, stderr, stdin, emitter, capturedStdin: () => captured };
}

function buildChildMock(options: FakeChildOptions, onStdin: (data: string) => void) {
  const child = fakeChild(options, onStdin);
  const mock: Record<string, unknown> = {
    stdout: child.stdout,
    stderr: child.stderr,
    stdin: child.stdin,
    once: (event: string, handler: (...args: unknown[]) => void): unknown => {
      child.emitter.once(event, handler);
      return mock;
    },
    on: (event: string, handler: (...args: unknown[]) => void): unknown => {
      child.emitter.on(event, handler);
      return mock;
    },
    kill: (): void => undefined,
  };
  return { mock, child };
}

describe('createClaudeRunner', () => {
  it('spawns claude with `-p --resume <sessionId>` args and pipes prompt via stdin', async () => {
    let capturedCommand = '';
    let capturedArgs: readonly string[] = [];
    let capturedStdin = '';
    const spawnFn = vi.fn().mockImplementation((command: string, args: string[]) => {
      capturedCommand = command;
      capturedArgs = args;
      const { mock } = buildChildMock({ stdout: 'Here is the draft.', exitCode: 0 }, (data) => {
        capturedStdin = data;
      });
      return mock;
    });

    const runner = createClaudeRunner({
      logger: silentLogger(),
      spawnFn: spawnFn as unknown as typeof import('node:child_process').spawn,
    });

    const result = await runner.run({ sessionId: 'session-abc', prompt: 'Draft release notes.' });

    expect(result.stdout).toBe('Here is the draft.');
    expect(capturedCommand).toBe('claude');
    expect(capturedArgs).toEqual(['-p', '--resume', 'session-abc']);
    expect(capturedStdin).toBe('Draft release notes.');
  });

  it('rejects when the subprocess exits with a non-zero code', async () => {
    const spawnFn = vi.fn().mockImplementation(() => {
      const { mock } = buildChildMock(
        { stdout: '', stderr: 'boom', exitCode: 2, signal: null },
        () => undefined,
      );
      return mock;
    });

    const runner = createClaudeRunner({
      logger: silentLogger(),
      spawnFn: spawnFn as unknown as typeof import('node:child_process').spawn,
    });

    await expect(runner.run({ sessionId: 'sid', prompt: 'x' })).rejects.toThrow(/code=2/);
  });

  it('honours a custom command and base args override', async () => {
    let capturedArgs: readonly string[] = [];
    const spawnFn = vi.fn().mockImplementation((_command: string, args: string[]) => {
      capturedArgs = args;
      const { mock } = buildChildMock({ stdout: 'ok', exitCode: 0 }, () => undefined);
      return mock;
    });

    const runner = createClaudeRunner({
      logger: silentLogger(),
      command: 'custom-claude',
      baseArgs: ['--print', '--session-id'],
      spawnFn: spawnFn as unknown as typeof import('node:child_process').spawn,
    });

    await runner.run({ sessionId: 'sid-9', prompt: 'hi' });

    expect(capturedArgs).toEqual(['--print', '--session-id', 'sid-9']);
  });
});
