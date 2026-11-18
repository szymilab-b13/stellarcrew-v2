import { createServer, type IncomingMessage, type Server, type ServerResponse } from 'node:http';
import type { AddressInfo } from 'node:net';
import type { Logger } from 'pino';

export interface HealthcheckServerHandle {
  readonly port: number;
  stop(): Promise<void>;
}

const HEALTHCHECK_PATH = '/health';

function handleRequest(req: IncomingMessage, res: ServerResponse): void {
  if (req.method === 'GET' && req.url === HEALTHCHECK_PATH) {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok' }));
    return;
  }

  res.writeHead(404, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ error: 'not_found' }));
}

export function startHealthcheckServer(options: {
  port: number;
  logger: Logger;
}): Promise<HealthcheckServerHandle> {
  const { port, logger } = options;
  const server: Server = createServer(handleRequest);

  return new Promise<HealthcheckServerHandle>((resolve, reject) => {
    const onError = (error: Error): void => {
      reject(error);
    };

    server.once('error', onError);
    server.listen(port, '0.0.0.0', () => {
      server.removeListener('error', onError);
      const address = server.address() as AddressInfo;
      logger.info({ port: address.port }, 'Healthcheck server listening');

      resolve({
        port: address.port,
        stop(): Promise<void> {
          return new Promise<void>((stopResolve, stopReject) => {
            server.close((error) => {
              if (error) {
                stopReject(error);
                return;
              }
              stopResolve();
            });
          });
        },
      });
    });
  });
}
