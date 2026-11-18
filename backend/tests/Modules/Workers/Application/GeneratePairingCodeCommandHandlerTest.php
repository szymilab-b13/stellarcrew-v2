<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workers\Application;

use App\Tests\Fixtures\Workers\InMemoryWorkerPairingCodeRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\UserId;
use Modules\Workers\Application\GeneratePairingCodeCommand;
use Modules\Workers\Application\GeneratePairingCodeCommandHandler;
use Modules\Workers\Infrastructure\Support\RandomPairingCodeGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class GeneratePairingCodeCommandHandlerTest extends TestCase
{
    public function testShouldPersistCodeWithOwnerAndTtlWhenInvoked(): void
    {
        $codes = new InMemoryWorkerPairingCodeRepository();
        $owner = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-18T15:00:00+00:00');
        $sut = new GeneratePairingCodeCommandHandler($codes, new RandomPairingCodeGenerator(), $this->fixedClock($now));

        $result = $sut(new GeneratePairingCodeCommand($owner));

        self::assertTrue($result->ownerUserId()->equals($owner));
        self::assertEquals($now->modify('+600 seconds'), $result->expiresAt());
        self::assertSame($result, $codes->findByCode($result->code()));
        self::assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $result->code());
    }

    private function fixedClock(DateTimeImmutable $now): Clock
    {
        return new readonly class ($now) implements Clock {
            public function __construct(private DateTimeImmutable $now)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->now;
            }
        };
    }
}
