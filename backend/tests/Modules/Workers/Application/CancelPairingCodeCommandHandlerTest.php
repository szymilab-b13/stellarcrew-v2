<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerPairingCodeRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Application\CancelPairingCodeCommand;
use Modules\Workers\Application\CancelPairingCodeCommandHandler;
use Modules\Workers\Domain\WorkerPairingCode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class CancelPairingCodeCommandHandlerTest extends TestCase
{
    public function testShouldDeleteCodeWhenOwnerCancels(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('ABCD-3456', $owner, new DateTimeImmutable('2026-11-21T13:00:00+00:00')));
        $sut = new CancelPairingCodeCommandHandler($codes);

        $sut(new CancelPairingCodeCommand('ABCD-3456', $owner));

        self::assertNull($codes->findByCode('ABCD-3456'));
    }

    public function testShouldThrow404WhenCodeDoesNotExist(): void
    {
        $sut = new CancelPairingCodeCommandHandler(new InMemoryWorkerPairingCodeRepository());

        $this->expectException(NotFoundHttpException::class);
        $sut(new CancelPairingCodeCommand('ZZZZ-9999', new UserId(Uuid::v7())));
    }

    public function testShouldThrow403WhenActorIsNotOwner(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $other = new UserId(Uuid::v7());
        $codes->save(WorkerPairingCode::issue('BCDE-3456', $owner, new DateTimeImmutable('2026-11-21T13:00:00+00:00')));
        $sut = new CancelPairingCodeCommandHandler($codes);

        $this->expectException(AccessDeniedHttpException::class);
        $sut(new CancelPairingCodeCommand('BCDE-3456', $other));
    }

    public function testShouldThrow409WhenCodeAlreadyClaimed(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $code = WorkerPairingCode::issue('CDEF-3456', $owner, new DateTimeImmutable('2026-11-21T13:00:00+00:00'));
        $code->claim(new DateTimeImmutable('2026-11-21T13:01:00+00:00'));

        $codes->save($code);
        $sut = new CancelPairingCodeCommandHandler($codes);

        $this->expectException(ConflictHttpException::class);
        $sut(new CancelPairingCodeCommand('CDEF-3456', $owner));
    }
}
