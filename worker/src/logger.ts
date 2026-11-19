import { pino, type Logger } from 'pino';

export function createLogger(options: { level: string; workerName: string }): Logger {
  return pino({
    level: options.level,
    base: { worker: options.workerName },
    timestamp: pino.stdTimeFunctions.isoTime,
  });
}
