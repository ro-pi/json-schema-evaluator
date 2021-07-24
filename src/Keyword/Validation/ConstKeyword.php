<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class ConstKeyword extends AbstractKeyword implements KeywordInterface
{
    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $result = $context->createResultForKeyword($this);

        if (!$context->getDraft()->valuesAreEqual($context->getInstance(), $keywordValue)) {
            $result->setError('Value not allowed');
            return $result;
        }

        return $result;
    }
}