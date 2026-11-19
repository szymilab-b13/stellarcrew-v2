# StellarCrew Worker

Node.js (TypeScript) daemon that runs Claude Code agents as `claude -p --resume` subprocesses.

Consumes a per-worker RabbitMQ queue (`chat.worker.<id>.inbox`) and publishes reply chunks via Mercure.

## Status

Skeleton + pairing CLI (BL-CAR-006 + BL-CAR-007). No RabbitMQ consumer yet (BL-CAR-008).

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
- `pnpm pair` interactive pairing CLI (BL-CAR-007)
- `pnpm test` vitest run
- `pnpm typecheck` tsc --noEmit
- `pnpm lint` eslint
- `pnpm format` prettier --write

## Pairing (BL-CAR-007)

Generate a pairing code in the web admin (`/admin/workers/new` on Symfony) and run:

```sh
API_BASE_URL=https://app.stellarcrew-v2.test pnpm pair
```

The CLI prompts for the pairing code (`XXXX-XXXX`), worker name and optional host label, then
calls `POST /api/workers/pair` and writes credentials to `~/.stellarcrew/worker.json`
(`chmod 600`). The file holds `workerId`, `secretToken`, `rabbitmqUser` and `pairedAt`. The
daemon reads it on startup in BL-CAR-008.

## Environment variables

| Name                      | Default                             | Purpose                                                                               |
| ------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------- |
| `RABBITMQ_URL`            | `amqp://guest:guest@rabbitmq:5672/` | Base AMQP URL (worker-specific credentials come from pairing, consumer in BL-CAR-008) |
| `HEALTHCHECK_PORT`        | `3001`                              | HTTP port for `GET /health`                                                           |
| `LOG_LEVEL`               | `info`                              | pino log level (`trace`/`debug`/`info`/`warn`/`error`/`fatal`)                        |
| `WORKER_NAME`             | `stellarcrew-worker`                | Display name in logs (pre-pairing)                                                    |
| `API_BASE_URL`            | `http://localhost:8080`             | Base URL for the Symfony API (used by `pnpm pair`)                                    |
| `WORKER_CREDENTIALS_PATH` | `$HOME/.stellarcrew/worker.json`    | Override location of the paired credentials file                                      |

## References

- Brief: [`knowledge/briefs/14-chat-agent-reply-via-terminal.md`](../knowledge/briefs/14-chat-agent-reply-via-terminal.md)
- ADR: [`knowledge/adr/ADR-BE-007-chat-agent-reply-worker.md`](../knowledge/adr/ADR-BE-007-chat-agent-reply-worker.md)
