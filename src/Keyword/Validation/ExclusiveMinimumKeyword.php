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

class ExclusiveMinimumKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'exclusiveMinimum';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $number = $context->getDraft()->createBigNumber($keywordValue, $context->config->acceptNumericStrings);

        if (!$number) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a number',
                $this,
                $context
            );
        }

        $keywordValue = $number;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instanceNumber = $context->getDraft()->createBigNumber(
            $context->getInstance(),
            $context->staticEvaluationContext->config->acceptNumericStrings
        );

        if (!$instanceNumber) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($instanceNumber->lessThanOrEquals($keywordValue)) {
            $result->setError(
                'A number greater than '
                . $keywordValue
                . ' required, but was '
                . $context->getInstance()
            );

            return $result;
        }

        return $result;
    }
}