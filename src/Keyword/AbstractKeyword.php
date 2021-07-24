<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

abstract class AbstractKeyword implements KeywordInterface
{
    private ?int $priority = null;

    public function getName(): string
    {
        return lcfirst(substr(preg_replace('@.*\\\\@', '', get_class($this)), 0, -7));
    }

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