import 'dotenv-flow/config';

import { createInterface } from 'node:readline/promises';

import { createCredentialsStore, defaultCredentialsPath } from './pairing/credentialsStore.js';
import { createPairingClient, PairingClientError } from './pairing/pairingClient.js';
import { createReadlinePrompts } from './pairing/promptPairing.js';
import { runPairing } from './pairing/runPairing.js';

const DEFAULT_API_BASE_URL = 'http://localhost:8080';

async function main(): Promise<void> {
  const apiBaseUrl = process.env.API_BASE_URL ?? DEFAULT_API_BASE_URL;
  const credentialsPath = defaultCredentialsPath();

  const rl = createInterface({ input: process.stdin, output: process.stdout });
  const prompts = createReadlinePrompts({ rl, stdout: process.stdout, stderr: process.stderr });
  const store = createCredentialsStore(credentialsPath);

  prompts.info(`Pairing against ${apiBaseUrl}`);
  prompts.info(`Credentials will be written to ${credentialsPath}`);

  try {
    await runPairing({
      apiBaseUrl,
      client: createPairingClient(),
      store,
      prompts,
      now: () => new Date(),
    });
  } finally {
    rl.close();
  }
}

main().catch((error: unknown) => {
  if (error instanceof PairingClientError) {
    process.exit(2);
  }
  console.error(error instanceof Error ? error.message : String(error));
  process.exit(1);
});
