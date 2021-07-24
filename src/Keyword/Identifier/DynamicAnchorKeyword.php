<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Identifier;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;

class DynamicAnchorKeyword extends AnchorKeyword
{
    public function getName(): string
    {
        return '$dynamicAnchor';
    }

    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        parent::evaluateStatic($keywordValue, $context);

        $context->registerDynamicAnchor($keywordValue);
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        return $context->createResultForKeyword($this);
    }
}