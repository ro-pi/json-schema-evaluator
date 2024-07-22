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

class EnumKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'enum';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_array($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be an array.',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var list<mixed> $keywordValue */

        $result = $context->createResultForKeyword($this, $keywordValue);

        foreach ($keywordValue as $enumElement) {
            if ($context->draft->valuesAreEqual($context->getCurrentInstance(), $enumElement)) {
                return $result;
            }
        }

        $result->invalidate('Value not allowed');
        return $result;
    }
}