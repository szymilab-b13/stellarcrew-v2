<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Auth\Domain\UserRole;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

use function usort;

final readonly class MenuAssembler
{
    /**
     * @param iterable<MenuContributor> $contributors
     */
    public function __construct(
        #[AutowireIterator('core.menu_contributor')]
        private iterable $contributors,
    ) {
    }

    /**
     * @return list<MenuSection>
     */
    public function assemble(AuthUser $user, UserRole $role): array
    {
        /** @var array<string, MenuSection> $merged */
        $merged = [];
        foreach ($this->contributors as $contributor) {
            foreach ($contributor->contribute($user) as $section) {
                $filteredItems = $this->filterItems($section->items, $role);
                if ($filteredItems === []) {
                    continue;
                }

                $merged[$section->id] = $this->mergeSection($merged[$section->id] ?? null, $section, $filteredItems);
            }
        }

        $sections = array_values($merged);
        usort($sections, static fn (MenuSection $a, MenuSection $b): int => $a->order <=> $b->order);

        return $sections;
    }

    /**
     * @param list<MenuItem> $items
     *
     * @return list<MenuItem>
     */
    private function filterItems(array $items, UserRole $role): array
    {
        $visible = [];
        foreach ($items as $item) {
            if ($item->requiresRole !== null && $role !== UserRole::Admin && $role->value !== $item->requiresRole) {
                continue;
            }

            $visible[] = $item;
        }

        usort($visible, static fn (MenuItem $a, MenuItem $b): int => $a->order <=> $b->order);

        return $visible;
    }

    /**
     * @param list<MenuItem> $filteredItems
     */
    private function mergeSection(?MenuSection $existing, MenuSection $incoming, array $filteredItems): MenuSection
    {
        if (!$existing instanceof MenuSection) {
            return new MenuSection($incoming->id, $incoming->labelKey, $incoming->order, $filteredItems);
        }

        $items = [...$existing->items, ...$filteredItems];
        usort($items, static fn (MenuItem $a, MenuItem $b): int => $a->order <=> $b->order);

        return new MenuSection(
            $existing->id,
            $existing->labelKey,
            min($existing->order, $incoming->order),
            $items,
        );
    }
}
