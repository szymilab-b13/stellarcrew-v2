# ADR-BE-004 — JWT authentication (access tokens)

Status: Accepted
Date: 2026-11-06

## Context

Milestone AUTH needs a way to authenticate users against the backend. Two mainstream options:

1. **Stateful session + cookie** (Symfony `json_login` + `session` + Redis storage). Simpler for a server-rendered app, requires a shared session store, inherits CSRF constraints.
2. **Stateless JWT access token** (`lexik/jwt-authentication-bundle`, RS256). Fits an SPA + API layout, easy to extend to mobile/third-party clients later, forces cleaner API design from day one.

The product is a React SPA calling an API. The team expects additional clients (mobile, agent SDKs, third-party automations). "Pay the implementation cost now instead of migrating later" is the explicit preference.

## Decision

- Use **`lexik/jwt-authentication-bundle`** with **RS256** (asymmetric keypair under `backend/config/jwt/`, both `*.pem` files ignored by Git).
- Access token **TTL = 3600 s (1 h)**, configurable via `JWT_TTL_SECONDS` env (default 3600).
- **No refresh token in BL-AUTH-001.** The client re-logs on 401. Refresh will land in a follow-up BL once it starts hurting; at that point we use an HttpOnly cookie for the refresh token.
- Access token is carried in the `Authorization: Bearer <token>` header. The SPA stores it in `localStorage` under `sc-auth-token`.
- Symfony security firewall `api` matches `^/api`, **stateless**, provider `app_users` (`Modules\Auth\Infrastructure\Security\DoctrineAuthUserProvider`), authenticator `jwt`.
- The provider returns `SecurityUser`, an `Infrastructure\Security` adapter that wraps `Modules\Auth\Domain\User`. The domain entity stays free of Symfony Security interfaces.
- Login endpoint: `POST /api/auth/login` (controller `AuthController::login`) — uses the same `#[MapRequestPayload]` pattern from ADR-BE-005. Returns `{ token, user: { email, display_name, role } }`.
- `GET /api/auth/me` — requires a valid token, returns the current user DTO.
- `POST /api/auth/logout` — returns 204. JWT is stateless so logout is a client-side discard; the endpoint exists for symmetry and future CSRF-protected session variants.
- Public access-control list (`^/api/install`, `^/api/install/status`, `^/api/health`, `^/api/ready`, `^/api/auth/login`, `^/api/auth/logout`). Everything else under `^/api` requires `IS_AUTHENTICATED_FULLY`.

## Operational notes

- Generate keypair locally with `make jwt-keys` (wraps `bin/console lexik:jwt:generate-keypair`). Passphrase lives in `.env.local` (ignored) in dev, and `.env.test` in test (hardcoded for determinism). In prod the passphrase is injected via the secrets backend.
- CI must regenerate the keypair before running `phpunit`. Use `make jwt-keys` in the workflow with a test-only passphrase matching `.env.test`.
- Rotation: generate a new keypair, shift `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` to the new one, keep the old public key as a secondary verifier during the transition window (Lexik supports `public_keys` arrays).

## Consequences

- Login flow is independent of session storage; no Redis dependency for auth.
- `/api/auth/*` endpoints follow the same request-mapping convention as every other endpoint (ADR-BE-005).
- Token revocation requires either shorter TTL + refresh (future BL-AUTH-004) or a token denylist (JTI tracked in Redis). Acceptable for the current phase.
- `SecurityUser` keeps the Symfony contract out of the domain layer. Future modules that need to resolve the authenticated user import it via the Auth module API, not by touching the adapter directly.
