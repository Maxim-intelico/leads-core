<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Psr\Log\AbstractLogger;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array}> */
    public array $records = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string)$message, 'context' => $context];
    }
}
