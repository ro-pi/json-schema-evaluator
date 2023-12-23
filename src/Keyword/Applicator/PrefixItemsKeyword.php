<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class PrefixItemsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'prefixItems';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_array($keywordValue) || !$keywordValue) {
            throw new InvalidKeywordValueException(
                'The \'%s\' must be a non-empty array.',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $prefixItemSchemaKey => $prefixItemSchema) {
            $context->pushSchema(keywordLocationFragment: (string)$prefixItemSchemaKey);

            if (!$prefixItemSchema instanceof \stdClass && !is_bool($prefixItemSchema)) {
                throw new InvalidKeywordValueException(
                    'The array elements of \'%s\' must be valid JSON Schemas.',
                    $this,
                    $context
                );
            }

            $context->setCurrentSchema($prefixItemSchema);
            $context->draft->evaluateStatic($context);

            $context->popSchema();
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var list<\stdClass> $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        $instanceKeys = array_keys($instance);
        $prefixItemsKey = null;

        foreach ($keywordValue as $prefixItemsKey => $prefixItemSchema) {
            if (!array_key_exists($prefixItemsKey, $instanceKeys)) {
                break;
            }

            $instanceKey = $instanceKeys[$prefixItemsKey];

            $context->pushSchema($prefixItemSchema, (string)$prefixItemsKey);
            $context->pushInstance($instance[$instanceKey], (string)$instanceKey);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }
        }

        $result->setAnnotation(($prefixItemsKey === count($instance) - 1) ?: $prefixItemsKey);

        return $result;
    }
}