<?php

declare(strict_types=1);

namespace App\Tests\Modules\Audit\Infrastructure;

use App\Tests\Fixtures\Audit\StubUndoDescriber;
use App\Tests\Fixtures\Audit\StubUndoer;
use Modules\Audit\Infrastructure\Service\UndoDispatcher;
use PHPUnit\Framework\TestCase;

final class UndoDispatcherTest extends TestCase
{
    public function testShouldReturnUndoerWhenItSupportsAction(): void
    {
        $matching = new StubUndoer(supports: 'user_management.user.deactivated');
        $other = new StubUndoer(supports: 'workspaces.workspace.deleted');
        $sut = new UndoDispatcher([$other, $matching], []);

        $found = $sut->findUndoerFor('user_management.user.deactivated');

        self::assertSame($matching, $found);
    }

    public function testShouldReturnNullWhenNoUndoerSupportsAction(): void
    {
        $sut = new UndoDispatcher([new StubUndoer(supports: 'workspaces.workspace.deleted')], []);

        $found = $sut->findUndoerFor('user_management.user.password_reset');

        self::assertNull($found);
    }

    public function testShouldReturnDescriberWhenItSupportsAction(): void
    {
        $matching = new StubUndoDescriber(supports: 'user_management.user.deactivated', text: 'Reaktywuj');
        $other = new StubUndoDescriber(supports: 'workspaces.workspace.deleted', text: 'Przywróć');
        $sut = new UndoDispatcher([], [$other, $matching]);

        $found = $sut->findDescriberFor('user_management.user.deactivated');

        self::assertSame($matching, $found);
    }

    public function testShouldReturnNullWhenNoDescriberSupportsAction(): void
    {
        $sut = new UndoDispatcher([], [new StubUndoDescriber(supports: 'workspaces.workspace.deleted', text: 'x')]);

        $found = $sut->findDescriberFor('user_management.user.password_reset');

        self::assertNull($found);
    }
}
