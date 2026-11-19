<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class SpyMessageBus implements MessageBusInterface
{
    /** @var list<Envelope> */
    public array $envelopes = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $envelope = $message instanceof Envelope ? $message->with(...$stamps) : new Envelope($message, $stamps);
        $this->envelopes[] = $envelope;

        return $envelope;
    }
}
