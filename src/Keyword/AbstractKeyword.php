<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

abstract class AbstractKeyword implements KeywordInterface
{
    public function __construct(
        private int $priority
    ) {}

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}