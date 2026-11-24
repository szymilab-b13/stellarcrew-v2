import { spawn, type ChildProcess } from 'node:child_process';
import type { Logger } from 'pino';

export interface ClaudeRunInput {
  readonly sessionId: string;
  readonly prompt: string;
  readonly resume?: boolean;
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
  readonly resumeFlag?: string;
  readonly newSessionFlag?: string;
  readonly cwd?: string;
  readonly timeoutMs?: number;
  readonly spawnFn?: typeof spawn;
}

const DEFAULT_COMMAND = 'claude';
const DEFAULT_BASE_ARGS: readonly string[] = ['-p'];
const DEFAULT_RESUME_FLAG = '--resume';
const DEFAULT_NEW_SESSION_FLAG = '--session-id';
const DEFAULT_TIMEOUT_MS = 120_000;

export function createClaudeRunner({
  logger,
  command = DEFAULT_COMMAND,
  baseArgs = DEFAULT_BASE_ARGS,
  resumeFlag = DEFAULT_RESUME_FLAG,
  newSessionFlag = DEFAULT_NEW_SESSION_FLAG,
  cwd,
  timeoutMs = DEFAULT_TIMEOUT_MS,
  spawnFn = spawn,
}: ClaudeRunnerOptions): ClaudeRunner {
  return {
    run: ({ sessionId, prompt, resume = false }) =>
      new Promise<ClaudeRunResult>((resolve, reject) => {
        const modeFlag = resume ? resumeFlag : newSessionFlag;
        const args = [...baseArgs, modeFlag, sessionId];
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
