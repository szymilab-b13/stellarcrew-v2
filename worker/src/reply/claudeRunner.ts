import { spawn, type ChildProcess } from 'node:child_process';
import type { Logger } from 'pino';

export interface ClaudeRunInput {
  readonly sessionId: string;
  readonly prompt: string;
}

export interface ClaudeRunResult {
  readonly stdout: string;
}

export interface ClaudeRunner {
  readonly run: (input: ClaudeRunInput) => Promise<ClaudeRunResult>;
}

export interface ClaudeRunnerOptions {
  readonly logger: Logger;
  readonly command?: string;
  readonly baseArgs?: readonly string[];
  readonly cwd?: string;
  readonly timeoutMs?: number;
  readonly spawnFn?: typeof spawn;
}

const DEFAULT_COMMAND = 'claude';
const DEFAULT_BASE_ARGS: readonly string[] = ['-p', '--session-id'];
const DEFAULT_TIMEOUT_MS = 120_000;

export function createClaudeRunner({
  logger,
  command = DEFAULT_COMMAND,
  baseArgs = DEFAULT_BASE_ARGS,
  cwd,
  timeoutMs = DEFAULT_TIMEOUT_MS,
  spawnFn = spawn,
}: ClaudeRunnerOptions): ClaudeRunner {
  return {
    run: ({ sessionId, prompt }) =>
      new Promise<ClaudeRunResult>((resolve, reject) => {
        const args = [...baseArgs, sessionId];
        const child: ChildProcess = spawnFn(command, args, {
          cwd,
          stdio: ['pipe', 'pipe', 'pipe'],
        });

        const stdoutChunks: Buffer[] = [];
        const stderrChunks: Buffer[] = [];

        child.stdout?.on('data', (chunk: Buffer) => stdoutChunks.push(chunk));
        child.stderr?.on('data', (chunk: Buffer) => stderrChunks.push(chunk));

        const timeout = setTimeout(() => {
          logger.warn({ sessionId, timeoutMs }, 'Claude subprocess timeout, killing');
          child.kill('SIGKILL');
        }, timeoutMs);

        child.once('error', (error) => {
          clearTimeout(timeout);
          reject(error);
        });

        child.once('close', (code, signal) => {
          clearTimeout(timeout);
          const stdout = Buffer.concat(stdoutChunks).toString('utf8');
          const stderr = Buffer.concat(stderrChunks).toString('utf8');
          if (code !== 0) {
            reject(
              new Error(
                `claude exited with code=${String(code)} signal=${String(signal)} stderr=${stderr.trim()}`,
              ),
            );
            return;
          }
          resolve({ stdout });
        });

        child.stdin?.end(prompt, 'utf8');
      }),
  };
}
