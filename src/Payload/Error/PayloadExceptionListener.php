<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * RFC 9457 (application/problem+json) для исключений нового маппера.
 * Легаси-подписчики и их форматы не затрагиваются: guard по своему интерфейсу.
 *
 * errors — массив даже в режиме fail-fast (всегда один элемент): клиенты сразу
 * пишут итерацию, и включение накопления позже не сломает контракт.
 */
final class PayloadExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (($exception instanceof HttpProblemException) === false) {
            return;
        }

        $response = new JsonResponse(
            data: [
                'type' => $exception->getProblemType(),
                'title' => $exception->getTitle(),
                'status' => $exception->getStatusCode(),
                'errors' => $exception->getViolations(),
            ],
            status: $exception->getStatusCode(),
            headers: ['Content-Type' => 'application/problem+json'],
        );

        $event->setResponse($response);
    }
}
