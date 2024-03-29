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

class MinLengthKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'minLength';
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
        if (!is_string($instance) || !$keywordValue) {
            // Ignore keyword also if 0 (same as default behavior)
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);
        $instanceLength = mb_strlen($instance, 'UTF-8');

        if ($instanceLength < $keywordValue) {
            $result->invalidate(
                'At least '
                . $keywordValue
                . ' characters are required, but there are only '
                . $instanceLength,
                $instanceLength
            );
        }

        return $result;
    }
}