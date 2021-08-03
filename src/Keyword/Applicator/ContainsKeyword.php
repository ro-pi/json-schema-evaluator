<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class ContainsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'contains';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue) && !is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid JSON Schema',
                $this,
                $context
            );
        }

        $context->pushSchema($keywordValue);
        $context->draft->evaluateStatic($context);
        $context->popSchema();
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $currentSchema = $context->getCurrentSchema();
        $result = $context->createResultForKeyword($this);

        $matchedIndexes = [];

        foreach ($instance as $instanceIndex => &$instanceValue) {
            $context->pushSchema($keywordValue);
            $context->pushInstance($instanceValue, (string) $instanceIndex);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if ($valid) {
                $matchedIndexes[$instanceIndex] = $instanceIndex;
            }
        }

        if ($matchedIndexes) {
            ksort($matchedIndexes);
        } else if (!isset($currentSchema->minContains) || $currentSchema->minContains > 0) {
            $result->invalidate('No item matches schema');
            return $result;
        }

        $result->setAnnotation((count($matchedIndexes) === count($instance)) ?: $matchedIndexes);

        return $result;
    }
}