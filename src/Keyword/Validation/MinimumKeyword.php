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

class MinimumKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'minimum';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $number = $context->draft->tryCreateNumber($keywordValue);

        if (!$number) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a number.',
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

        if (!$instanceNumber) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        if ($instanceNumber->lessThan($keywordValue)) {
            $result->invalidate(
                'A number greater than or equal to '
                . $keywordValue
                . ' required, but was '
                . $context->getCurrentInstance()
            );
        }

        return $result;
    }
}