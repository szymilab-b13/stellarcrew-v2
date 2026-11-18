import { describe, expect, it } from 'vitest';

import { loadConfig } from './config.js';

describe('loadConfig', () => {
  it('should default healthcheck port to 3001 when env is empty', () => {
    const config = loadConfig({});

    expect(config.healthcheckPort).toBe(3001);
  });

  it('should parse HEALTHCHECK_PORT env as an integer', () => {
    const config = loadConfig({ HEALTHCHECK_PORT: '4242' });

    expect(config.healthcheckPort).toBe(4242);
  });

  it('should throw when HEALTHCHECK_PORT is not a parsable integer', () => {
    expect(() => loadConfig({ HEALTHCHECK_PORT: 'abc' })).toThrow(/HEALTHCHECK_PORT/);
  });

  it('should throw when HEALTHCHECK_PORT is out of the TCP port range', () => {
    expect(() => loadConfig({ HEALTHCHECK_PORT: '99999' })).toThrow(/HEALTHCHECK_PORT/);
  });

  it('should default log level to info', () => {
    const config = loadConfig({});

    expect(config.logLevel).toBe('info');
  });

  it('should default worker name to stellarcrew-worker', () => {
    const config = loadConfig({});

    expect(config.workerName).toBe('stellarcrew-worker');
  });

  it('should default rabbit mq url to the compose service host', () => {
    const config = loadConfig({});

    expect(config.rabbitMqUrl).toBe('amqp://guest:guest@rabbitmq:5672/');
  });

  it('should override rabbit mq url from the env', () => {
    const config = loadConfig({ RABBITMQ_URL: 'amqp://u:p@host:5672/vhost' });

    expect(config.rabbitMqUrl).toBe('amqp://u:p@host:5672/vhost');
  });
});
