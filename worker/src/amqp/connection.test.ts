import { describe, expect, it } from 'vitest';

import { buildAmqpUrl, maskAmqpUrl } from './connection.js';

describe('buildAmqpUrl', () => {
  it('builds a fully url-encoded amqp URL from rabbitmq config + credentials', () => {
    const url = buildAmqpUrl(
      { host: 'rabbit', port: 5672, vhost: '/' },
      {
        workerId: '01976b7a-9f12-7a5e-b7e2-5c9d2b8a0f00',
        secretToken: 'se@cret:/pass',
        rabbitmqUser: 'worker-01976b7a',
        pairedAt: '2026-11-18T18:00:00Z',
      },
    );

    expect(url).toBe('amqp://worker-01976b7a:se%40cret%3A%2Fpass@rabbit:5672/%2F');
  });

  it('keeps a non-root vhost intact when it has no reserved chars', () => {
    const url = buildAmqpUrl(
      { host: 'broker.internal', port: 5673, vhost: 'stellarcrew' },
      {
        workerId: 'w',
        secretToken: 'token',
        rabbitmqUser: 'user-01',
        pairedAt: 'x',
      },
    );

    expect(url).toBe('amqp://user-01:token@broker.internal:5673/stellarcrew');
  });
});

describe('maskAmqpUrl', () => {
  it('masks the password segment between user: and @host', () => {
    expect(maskAmqpUrl('amqp://user:supersecret@host:5672/%2F')).toBe(
      'amqp://user:***@host:5672/%2F',
    );
  });

  it('leaves a URL without credentials unchanged', () => {
    expect(maskAmqpUrl('amqp://host:5672/%2F')).toBe('amqp://host:5672/%2F');
  });
});
