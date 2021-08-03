<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException;

interface RuntimeKeywordInterface extends KeywordInterface
{
    /**
     * @throws KeywordRuntimeEvaluationException
     */
    function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult;
}