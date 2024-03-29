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
use Ropi\JsonSchemaEvaluator\Type\NumberInterface;

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
        $number = $context->draft->tryCreateNumber($keywordValue);

        /** @var NumberInterface $zero */
        $zero = $context->draft->tryCreateNumber(0);

        if (!$number || $number->lessThanOrEquals($zero)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a number greater than 0.',
                $this,
                $context
            );
        }

        $keywordValue = $number;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var NumberInterface $keywordValue */

        $instanceNumber = $context->draft->tryCreateNumber($context->getCurrentInstance());
        if (!$instanceNumber instanceof NumberInterface) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        /** @var NumberInterface $zero */
        $zero = $context->draft->tryCreateNumber(0);

        if (!$instanceNumber->mod($keywordValue)->equals($zero)) {
            $result->invalidate(
                $context->getCurrentInstance()
                . ' is not a multiple of '
                . $keywordValue
            );
        }

        return $result;
    }
}