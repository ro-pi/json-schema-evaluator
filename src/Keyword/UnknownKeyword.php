<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;

class UnknownKeyword extends AbstractKeyword implements RuntimeKeywordInterface
{
    private string $name;

    public function __construct(int $priority, string $name)
    {
        parent::__construct($priority);
        $this->name = $name;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        return $context->createResultForKeyword($this, $keywordValue);
    }

    public function getName(): string
    {
        return $this->name;
    }
}