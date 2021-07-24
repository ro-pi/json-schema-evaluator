<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\RuntimeEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;

interface JsonSchemaEvaluatorInterface
{
    /**
     * @throws Draft\Exception\InvalidSchemaException
     * @throws Keyword\Exception\StaticKeywordAnalysisException
     */
    function evaluateStatic(object $jsonSchema, StaticEvaluationConfig $config): StaticEvaluationContext;

    /**
     * @param mixed $instance
     * @param StaticEvaluationContext $staticEvaluationContext
     * @param RuntimeEvaluationConfig|null $config
     * @param RuntimeEvaluationResult[] $results
     * @return bool
     */
    function evaluate(
        mixed &$instance,
        StaticEvaluationContext $staticEvaluationContext,
        ?RuntimeEvaluationConfig $config = null,
        array &$results = null
    ): bool;
}