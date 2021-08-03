<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

abstract class AbstractKeyword implements KeywordInterface
{
    private ?int $priority = null;

    abstract public function getName(): string;

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function hasPriority(): bool
    {
        return $this->priority !== null;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }
}