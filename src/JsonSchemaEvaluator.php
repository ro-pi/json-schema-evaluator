<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\RuntimeEvaluationConfig;
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
    public function evaluateStatic(object|bool $jsonSchema, StaticEvaluationConfig $config): StaticEvaluationContext
    {
        $context = new StaticEvaluationContext($jsonSchema, $config);

        $config->getDefaultDraft()->evaluateStatic($context);

        return $context;
    }

    /**
     * @param mixed $instance
     * @param StaticEvaluationContext $staticEvaluationContext
     * @param RuntimeEvaluationConfig|null $config
     * @param RuntimeEvaluationResult[] $results
     * @return bool
     */
    public function evaluate(
        mixed &$instance,
        StaticEvaluationContext $staticEvaluationContext,
        ?RuntimeEvaluationConfig $config = null,
        array &$results = null
    ): bool {
        if (!$config) {
            $config = new RuntimeEvaluationConfig();
        }

        $context = new RuntimeEvaluationContext(
            schema: $staticEvaluationContext->getSchema(),
            instance: $instance,
            config: $config,
            staticEvaluationContext: $staticEvaluationContext
        );

        $valid = $staticEvaluationContext->getConfig()->getDefaultDraft()->evaluate($context);
        $results = $context->getResults();

        return $valid;
    }
}