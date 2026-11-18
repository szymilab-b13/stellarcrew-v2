<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Modules\Core\Infrastructure\Bundle\CoreBundle::class => ['all' => true],
    Modules\Auth\Infrastructure\Bundle\AuthBundle::class => ['all' => true],
    Modules\Workspaces\Infrastructure\Bundle\WorkspacesBundle::class => ['all' => true],
    Modules\UserManagement\Infrastructure\Bundle\UserManagementBundle::class => ['all' => true],
    Modules\Audit\Infrastructure\Bundle\AuditBundle::class => ['all' => true],
    Modules\Projects\Infrastructure\Bundle\ProjectsBundle::class => ['all' => true],
    Modules\Organizations\Infrastructure\Bundle\OrganizationsBundle::class => ['all' => true],
    Modules\Agents\Infrastructure\Bundle\AgentsBundle::class => ['all' => true],
    Modules\Chat\Infrastructure\Bundle\ChatBundle::class => ['all' => true],
];
