<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;

class ConstKeyword extends AbstractKeyword implements RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'const';
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $result = $context->createResultForKeyword($this, $keywordValue);

        if (!$context->draft->valuesAreEqual($context->getCurrentInstance(), $keywordValue)) {
            $result->invalidate('Value not allowed');
        }

        return $result;
    }
}