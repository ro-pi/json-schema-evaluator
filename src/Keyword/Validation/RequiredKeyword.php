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

class RequiredKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'required';
    }

    /**
     * @throws StaticKeywordAnalysisException
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

        foreach ($keywordValue as $requiredPropertyKey => $requiredProperty) {
            $context->pushSchema(keywordLocationFragment: (string) $requiredPropertyKey);

            if (!is_string($requiredProperty)) {
                throw new InvalidKeywordValueException(
                    'The array elements of \'%s\' must be strings.',
                    $this,
                    $context
                );
            }

            $context->popSchema();
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_object($instance) || !$keywordValue) {
            //Ignore keyword also if empty (same as default behavior)
            return null;
        }

        $result = $context->createResultForKeyword($this);

        foreach ($keywordValue as $requiredPropertyKey => $requiredProperty) {
            if (!property_exists($instance, $requiredProperty)) {
                $context->pushSchema(keywordLocationFragment: (string) $requiredPropertyKey);

                $context->createResultForKeyword($this)->invalidate(
                    'Required property \''
                    . $requiredProperty
                    . '\' is missing.'
                );

                $context->popSchema();

                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }
        }

        return $result;
    }
}