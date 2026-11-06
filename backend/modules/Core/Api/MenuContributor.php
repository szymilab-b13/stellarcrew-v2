<?php

declare(strict_types=1);

namespace Modules\Core\Api;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuSection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('core.menu_contributor')]
interface MenuContributor
{
    /**
     * @return list<MenuSection>
     */
    public function contribute(AuthUser $user): array;
}
