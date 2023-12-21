<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;

interface JsonSchemaEvaluatorInterface
{
    /**
     * @throws Draft\Exception\InvalidSchemaException
     * @throws Keyword\Exception\StaticKeywordAnalysisException
     */
    function evaluateStatic(\stdClass $jsonSchema, StaticEvaluationConfig $config): StaticEvaluationContext;

    /**
     * @param mixed $instance
     * @param StaticEvaluationContext $staticEvaluationContext
     * @param RuntimeEvaluationResult[] $results
     * @return bool
     */
    function evaluate(
        mixed &$instance,
        StaticEvaluationContext $staticEvaluationContext,
        array &$results = null
    ): bool;
}