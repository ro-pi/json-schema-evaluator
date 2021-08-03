<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class MinContainsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'minContains';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_int($keywordValue) || $keywordValue < 0) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a non-negative integer',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getInstance();
        if (!is_array($instance) || $keywordValue === 1) {
            // Ignore keyword also if 1 (same as default behavior)
            return null;
        }

        $containsAnnotation = $context->getLastResultByKeywordName('contains')?->getAnnotation();
        if ($containsAnnotation === null) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($keywordValue === 0) {
            return $result;
        }

        if (is_array($containsAnnotation)) {
            $containsCount = count($containsAnnotation);
        } else {
            $containsCount = count($instance);
        }

        if ($containsCount < $keywordValue) {
            $result->setError(
                $keywordValue
                . ' or more matching elements must be contained, but there are '
                . $containsCount
            );

            return $result;
        }

        return $result;
    }
}