import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { pino } from 'pino';

import { startHealthcheckServer, type HealthcheckServerHandle } from './healthcheck.js';

const silentLogger = pino({ level: 'silent' });

describe('startHealthcheckServer', () => {
  let handle: HealthcheckServerHandle;

  beforeEach(async () => {
    handle = await startHealthcheckServer({ port: 0, logger: silentLogger });
  });

  afterEach(async () => {
    await handle.stop();
  });

  it('should respond 200 with status ok on GET /health', async () => {
    const response = await fetch(`http://127.0.0.1:${String(handle.port)}/health`);
    const body = (await response.json()) as Record<string, unknown>;

    expect(response.status).toBe(200);
    expect(body).toEqual({ status: 'ok' });
  });

  it('should respond 404 on unknown paths', async () => {
    const response = await fetch(`http://127.0.0.1:${String(handle.port)}/missing`);

    expect(response.status).toBe(404);
  });

  it('should respond 404 on non-GET methods for /health', async () => {
    const response = await fetch(`http://127.0.0.1:${String(handle.port)}/health`, {
      method: 'POST',
    });

    expect(response.status).toBe(404);
  });
});
