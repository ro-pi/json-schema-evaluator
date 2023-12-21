<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;

class JsonSchemaEvaluator implements JsonSchemaEvaluatorInterface
{
    /**
     * @throws Draft\Exception\InvalidSchemaException
     * @throws Keyword\Exception\StaticKeywordAnalysisException
     */
    public function evaluateStatic(\stdClass|bool $jsonSchema, StaticEvaluationConfig $config): StaticEvaluationContext
    {
        $context = new StaticEvaluationContext($jsonSchema, $config);

        $config->defaultDraft->evaluateStatic($context);

        return $context;
    }

    /**
     * @param mixed $instance
     * @param StaticEvaluationContext $staticEvaluationContext
     * @param RuntimeEvaluationResult[] $results
     * @return bool
     */
    public function evaluate(
        mixed &$instance,
        StaticEvaluationContext $staticEvaluationContext,
        array &$results = null
    ): bool {
        $context = new RuntimeEvaluationContext(
            $staticEvaluationContext->getCurrentSchema(),
            $instance,
            $staticEvaluationContext
        );

        $valid = $staticEvaluationContext->config->defaultDraft->evaluate($context);
        $results = $context->getResults();

        return $valid;
    }
}