# ADR-BE-005 — Request mapping and validation style

Status: Accepted
Date: 2026-11-06

## Context

Early controllers decoded the request body by hand, reading JSON or `multipart/form-data` into arrays and running a cascade of `requireString` / `optionalString` / `normaliseList` helpers. The approach grows linearly with every new field, buries validation inside Infrastructure, and makes the "what does this endpoint accept" question impossible to answer at a glance.

Symfony 7 ships two attributes that remove this entirely:

- `#[MapRequestPayload]` deserialises the body (JSON **or** `multipart/form-data`) into a typed DTO and runs Validator constraints declared on the DTO properties. Failure raises `ValidationFailedException`, which our `ExceptionListener` already maps to `422 Unprocessable Entity` with the per-field violation list.
- `#[MapUploadedFile]` resolves a single uploaded file (or a list) from the request, accepts its own `constraints:` (e.g. `Assert\File`, `Assert\Image`) and raises the same exception on failure.

## Decision

Every HTTP endpoint that accepts a body MUST:

1. Define a `final readonly class <Verb><Noun>Request` in `Modules/<Module>/Infrastructure/Http/` (co-located with the controller) with public promoted properties.
2. Declare validation on each property via `Symfony\Component\Validator\Constraints` attributes (`#[Assert\NotBlank]`, `#[Assert\Email]`, `#[Assert\Length]`, `#[Assert\Choice]`, `#[Assert\All]`, etc.).
3. Inject the DTO into the action with `#[MapRequestPayload]`.
4. Resolve uploaded files via `#[MapUploadedFile]` with inline `constraints:` for mime/size.
5. Never read `Request::getContent()`, `Request::$request`, or `Request::$files` from the action body.

The controller action is reduced to:

```php
public function install(
    #[MapRequestPayload] InstallRequest $input,
    #[MapUploadedFile(constraints: [new Assert\File(maxSize: '5M', mimeTypes: [...])])] ?UploadedFile $logo = null,
): JsonResponse {
    // call Application handler, return response
}
```

Domain-level invariants (e.g. "locale must be supported", "install cannot run twice") stay inside the Application handler. DTO-level validation covers shape, presence, basic types, length and simple enums.

Error handling contract:

- `422 Unprocessable Entity` → `ValidationFailedException`, carrying a list of `{field, message}` pairs under `error.details`.
- `400 Bad Request` → `BadRequestHttpException`, e.g. malformed JSON, missing content-type Symfony cannot deserialise.
- `404 / 403 / 401` → mapped by `ExceptionListener` as before.
- Domain-specific application exceptions keep their mapping (`InvalidInstallInputException` → 400, `SystemAlreadyInstalledException` → 404).

## Consequences

- Controllers become thin dispatchers (typically under 30 lines).
- Validation is documented declaratively next to the field, making the API contract readable and OpenAPI-friendly.
- Tests pin the status codes: expect `422` for shape/validation errors, `400` only for malformed envelopes or domain rejection.
- When adding `multipart/form-data` endpoints, add the same `#[MapUploadedFile]` style — no new helpers.
- JSON and form-urlencoded bodies are handled by the same code path.

## Reference implementation

`Modules\Core\Infrastructure\Http\InstallWizardController::install` + `InstallRequest`.
