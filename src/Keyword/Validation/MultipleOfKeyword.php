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
use Ropi\JsonSchemaEvaluator\Type\BigNumberInterface;

class MultipleOfKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'multipleOf';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $number = $context->getDraft()->createBigNumber($keywordValue);

        if (!$number || $number->lessThanOrEquals($context->getDraft()->createBigNumber(0))) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a number greater than 0',
                $this,
                $context
            );
        }

        $keywordValue = $number;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instanceNumber = $context->getDraft()->createBigNumber($context->getCurrentInstance());
        if (!$instanceNumber instanceof BigNumberInterface) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if (!$instanceNumber->mod($keywordValue)->equals($context->getDraft()->createBigNumber(0))) {
            $result->setError(
                $context->getCurrentInstance()
                . ' is not a multiple of '
                . $keywordValue
            );

            return $result;
        }

        return $result;
    }
}