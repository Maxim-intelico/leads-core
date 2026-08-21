<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\Clamp;
use Leads\Core\Payload\Attribute\FallbackTo;
use Leads\Core\Payload\Attribute\FromHeader;
use Leads\Core\Payload\Attribute\FromPath;
use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\HttpPayload;
use Leads\Core\Payload\Attribute\Normalized;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Плоская фикстура механизма: задействует каждый скалярный кастер, каждый декоратор
 * и каждый источник. Остаётся в проекте навсегда как регрессионный набор.
 */
#[HttpPayload(maxBodyBytes: 2048)]
final readonly class SampleRequest
{
    public function __construct(
        #[FromQuery]
        #[Normalized]
        #[FallbackTo(SampleSortField::CreatedAt)]
        public SampleSortField $sortBy = SampleSortField::CreatedAt,
        #[FromQuery]
        #[Clamp(1, 100)]
        public int $limit = 20,
        #[FromQuery]
        public int $page = 1,
        #[FromQuery]
        public ?string $email = null,
        #[FromQuery]
        public ?bool $archived = null,
        #[FromQuery]
        public ?\DateTimeImmutable $fromDate = null,
        #[FromQuery]
        public ?\DateTimeImmutable $toDate = null,
        #[FromPath]
        public ?string $projectId = null,
        #[FromHeader('X-Request-Source')]
        public ?string $requestSource = null,
    ) {
    }

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if ($this->fromDate !== null && $this->toDate !== null && $this->fromDate > $this->toDate) {
            $context->buildViolation('fromDate must be before or equal to toDate.')
                ->atPath('toDate')
                ->addViolation();
        }
    }
}
