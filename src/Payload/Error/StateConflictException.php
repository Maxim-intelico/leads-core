<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

use Symfony\Component\HttpFoundation\Response;

/**
 * 409: конфликт состояния (email занят и т.п.). Указатель тот же, что у ошибок
 * валидации ('body/email') — фронт кладёт сообщение под то же поле формы.
 * Бросается доменными адаптерами при переводе роутов; листенер умеет с первого дня.
 */
final class StateConflictException extends \RuntimeException implements HttpProblemException
{
    public function __construct(
        private readonly string $pointer,
        private readonly string $detail,
        private readonly string $errorCode = 'state.conflict',
    ) {
        parent::__construct($this->detail);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function getProblemType(): string
    {
        return '/errors/conflict';
    }

    public function getTitle(): string
    {
        return 'Request conflicts with the current state';
    }

    public function getViolations(): ViolationList
    {
        return new ViolationList([new Violation($this->pointer, $this->detail, $this->errorCode)]);
    }
}
