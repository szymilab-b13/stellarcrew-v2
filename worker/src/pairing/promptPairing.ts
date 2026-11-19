import type { Interface as ReadlineInterface } from 'node:readline/promises';

import type { WorkerCredentials } from './credentialsStore.js';
import type { PairingPrompts } from './runPairing.js';

export interface ReadlinePromptsDeps {
  readonly rl: ReadlineInterface;
  readonly stdout: NodeJS.WritableStream;
  readonly stderr: NodeJS.WritableStream;
}

export function createReadlinePrompts(deps: ReadlinePromptsDeps): PairingPrompts {
  const { rl, stdout, stderr } = deps;
  return {
    askCode: async () => rl.question('Pairing code (XXXX-XXXX): '),
    askName: async () => rl.question('Worker name: '),
    askHostLabel: async () => {
      const raw = await rl.question('Host label (optional, blank to skip): ');
      return raw === '' ? undefined : raw;
    },
    confirmOverwrite: async (existing: WorkerCredentials) => {
      stdout.write(
        `A worker is already paired on this host (workerId=${existing.workerId}, paired at ${existing.pairedAt}).\n`,
      );
      const answer = await rl.question('Overwrite existing credentials? [y/N]: ');
      return /^y(es)?$/iu.test(answer.trim());
    },
    info: (message: string) => {
      stdout.write(`${message}\n`);
    },
    error: (message: string) => {
      stderr.write(`${message}\n`);
    },
  };
}
