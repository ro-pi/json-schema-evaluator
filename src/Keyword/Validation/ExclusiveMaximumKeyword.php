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

class ExclusiveMaximumKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'exclusiveMaximum';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $number = $context->draft->createNumber($keywordValue);

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
        $instanceNumber = $context->draft->createNumber($context->getCurrentInstance());

        if (!$instanceNumber) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($instanceNumber->greaterThanOrEquals($keywordValue)) {
            $result->invalidate(
                'A number less than or equal to '
                . $keywordValue
                . ' required, but was '
                . $context->getCurrentInstance()
            );

            return $result;
        }

        return $result;
    }
}