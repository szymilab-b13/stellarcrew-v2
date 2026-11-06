<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http;

use Modules\Auth\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Infrastructure\Security\SecurityUser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final readonly class AuthController
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $passwords,
        private JWTTokenManagerInterface $tokens,
    ) {
    }

    #[Route('/api/auth/login', name: 'auth_login', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Authenticate with email + password, return a JWT access token and the current user.',
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function login(#[MapRequestPayload] LoginRequest $input): JsonResponse
    {
        $domainUser = $this->users->findByEmail($input->email);

        if (!$domainUser instanceof User) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        $securityUser = new SecurityUser($domainUser);

        if (!$this->passwords->isPasswordValid($securityUser, $input->password)) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return new JsonResponse([
            'token' => $this->tokens->create($securityUser),
            'user' => $this->serialiseUser($securityUser),
        ]);
    }

    #[Route('/api/auth/me', name: 'auth_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Return the currently authenticated user (requires JWT).',
        tags: ['auth'],
        responses: [
            new OA\Response(response: 200, description: 'Current user'),
            new OA\Response(response: 401, description: 'Missing or expired token'),
        ],
    )]
    public function me(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        return new JsonResponse($this->serialiseUser($user));
    }

    #[Route('/api/auth/logout', name: 'auth_logout', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout (no-op for JWT; client discards the access token).',
        tags: ['auth'],
        responses: [
            new OA\Response(response: 204, description: 'Logged out'),
        ],
    )]
    public function logout(): JsonResponse
    {
        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{email: string, display_name: ?string, role: string}
     */
    private function serialiseUser(SecurityUser $user): array
    {
        $domain = $user->domainUser();

        return [
            'email' => $domain->email(),
            'display_name' => $domain->displayName(),
            'role' => $domain->role()->value,
        ];
    }
}
