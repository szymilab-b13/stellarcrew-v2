<?php

declare(strict_types=1);

namespace Modules\Audit\Api\Exception;

use RuntimeException;

use function sprintf;

final class UndoConflictException extends RuntimeException
{
    public function __construct(
        private readonly string $conflictCode,
        string $message = '',
    ) {
        parent::__construct($message === '' ? sprintf('Undo conflict: %s', $conflictCode) : $message);
    }

    public function conflictCode(): string
    {
        return $this->conflictCode;
    }
}
