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

class MaxContainsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'maxContains';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_int($keywordValue) || $keywordValue < 0) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a non-negative integer.',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var int $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $containsAnnotation = $context->getLastResultByKeywordName('contains')?->getAnnotationValue();
        if ($containsAnnotation === null) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        if (is_array($containsAnnotation)) {
            $containsCount = count($containsAnnotation);
        } else {
            $containsCount = count($instance);
        }

        if ($containsCount > $keywordValue) {
            $result->invalidate(
                'At most '
                . $keywordValue
                . ' matched elements must be contained, but there are '
                . $containsCount,
                $containsCount
            );
        }

        return $result;
    }
}