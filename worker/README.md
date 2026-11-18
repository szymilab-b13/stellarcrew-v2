# StellarCrew Worker

Node.js (TypeScript) daemon that runs Claude Code agents as `claude -p --resume` subprocesses.

Consumes a per-worker RabbitMQ queue (`chat.worker.<id>.inbox`) and publishes reply chunks via Mercure.

## Status

Skeleton (BL-CAR-006). No RabbitMQ consumer yet (BL-CAR-008). No pairing flow yet (BL-CAR-007).

## Requirements

- Node 22 LTS (production target). Local Node 20 works for development.
- pnpm 9.x (same as frontend).

## Setup

```sh
cp .env.example .env
pnpm install
```

## Scripts

- `pnpm start` run the worker (tsx)
- `pnpm dev` watch mode
- `pnpm test` vitest run
- `pnpm typecheck` tsc --noEmit
- `pnpm lint` eslint
- `pnpm format` prettier --write

## Environment variables

| Name               | Default                             | Purpose                                                                               |
| ------------------ | ----------------------------------- | ------------------------------------------------------------------------------------- |
| `RABBITMQ_URL`     | `amqp://guest:guest@rabbitmq:5672/` | Base AMQP URL (worker-specific credentials come from pairing, consumer in BL-CAR-008) |
| `HEALTHCHECK_PORT` | `3001`                              | HTTP port for `GET /health`                                                           |
| `LOG_LEVEL`        | `info`                              | pino log level (`trace`/`debug`/`info`/`warn`/`error`/`fatal`)                        |
| `WORKER_NAME`      | `stellarcrew-worker`                | Display name in logs (pre-pairing)                                                    |

## References

- Brief: [`knowledge/briefs/14-chat-agent-reply-via-terminal.md`](../knowledge/briefs/14-chat-agent-reply-via-terminal.md)
- ADR: [`knowledge/adr/ADR-BE-007-chat-agent-reply-worker.md`](../knowledge/adr/ADR-BE-007-chat-agent-reply-worker.md)
