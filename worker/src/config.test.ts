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

  it('should default rabbitmq host/port/vhost', () => {
    const config = loadConfig({});

    expect(config.rabbitmq).toEqual({ host: 'localhost', port: 5672, vhost: '/' });
  });

  it('should override rabbitmq host/port/vhost from env', () => {
    const config = loadConfig({
      RABBITMQ_HOST: 'broker.internal',
      RABBITMQ_PORT: '5673',
      RABBITMQ_VHOST: 'stellarcrew',
    });

    expect(config.rabbitmq).toEqual({ host: 'broker.internal', port: 5673, vhost: 'stellarcrew' });
  });

  it('should throw when RABBITMQ_PORT is not an integer', () => {
    expect(() => loadConfig({ RABBITMQ_PORT: 'xyz' })).toThrow(/RABBITMQ_PORT/);
  });

  it('should default API_BASE_URL to http://localhost:8000', () => {
    const config = loadConfig({});

    expect(config.api.baseUrl).toBe('http://localhost:8000');
  });

  it('should default heartbeat interval to 30 seconds and accept env overrides', () => {
    const defaults = loadConfig({});

    expect(defaults.heartbeatIntervalMs).toBe(30000);

    const overridden = loadConfig({ HEARTBEAT_INTERVAL_MS: '5000' });

    expect(overridden.heartbeatIntervalMs).toBe(5000);
  });

  it('should throw when HEARTBEAT_INTERVAL_MS is outside the supported range', () => {
    expect(() => loadConfig({ HEARTBEAT_INTERVAL_MS: '500' })).toThrow(/HEARTBEAT_INTERVAL_MS/);
    expect(() => loadConfig({ HEARTBEAT_INTERVAL_MS: 'abc' })).toThrow(/HEARTBEAT_INTERVAL_MS/);
  });

  it('should strip the trailing slash from API_BASE_URL', () => {
    const config = loadConfig({ API_BASE_URL: 'https://app.stellarcrew-v2.test/' });

    expect(config.api.baseUrl).toBe('https://app.stellarcrew-v2.test');
  });
});
