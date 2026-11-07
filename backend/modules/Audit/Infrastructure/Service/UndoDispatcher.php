<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Service;

use Modules\Audit\Api\UndoDescriberInterface;
use Modules\Audit\Api\UndoerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class UndoDispatcher
{
    /**
     * @param iterable<UndoerInterface>         $undoers
     * @param iterable<UndoDescriberInterface>  $describers
     */
    public function __construct(
        #[AutowireIterator('audit.undoer')]
        private iterable $undoers,
        #[AutowireIterator('audit.undo_describer')]
        private iterable $describers,
    ) {
    }

    public function findUndoerFor(string $action): ?UndoerInterface
    {
        foreach ($this->undoers as $undoer) {
            if ($undoer->supports($action)) {
                return $undoer;
            }
        }

        return null;
    }

    public function findDescriberFor(string $action): ?UndoDescriberInterface
    {
        foreach ($this->describers as $describer) {
            if ($describer->supports($action)) {
                return $describer;
            }
        }

        return null;
    }
}
