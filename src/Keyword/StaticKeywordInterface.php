<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;

interface StaticKeywordInterface extends KeywordInterface
{
    /**
     * @throws StaticKeywordAnalysisException
     */
    function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void;
}