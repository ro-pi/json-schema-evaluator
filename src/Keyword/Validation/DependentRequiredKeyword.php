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

class DependentRequiredKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'dependentRequired';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!$keywordValue instanceof \stdClass) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be an object.',
                $this,
                $context
            );
        }

        foreach (get_object_vars($keywordValue) as $dependencyPropertyName => $requiredProperties) {
            $context->pushSchema(keywordLocationFragment: (string)$dependencyPropertyName);

            if (!is_array($requiredProperties)) {
                throw new InvalidKeywordValueException(
                    'The property \''
                    . $dependencyPropertyName
                    . '\' in \'%s\' object must be an array.',
                    $this,
                    $context
                );
            }

            foreach ($requiredProperties as $requiredPropertyKey => $requiredProperty) {
                $context->pushSchema(keywordLocationFragment: (string)$requiredPropertyKey);

                if (!is_string($requiredProperty)) {
                    throw new InvalidKeywordValueException(
                        'The array elements of property \''
                        . $dependencyPropertyName
                        . '\' in \'%s\' object must be strings.',
                        $this,
                        $context
                    );
                }

                $context->popSchema();
            }

            $context->popSchema();
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var \stdClass $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!$instance instanceof \stdClass) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        foreach (get_object_vars($keywordValue) as $dependencyPropertyName => $requiredProperties) {
            /** @var list<string> $requiredProperties */

            if (!property_exists($instance, $dependencyPropertyName)) {
                continue;
            }

            foreach ($requiredProperties as $requiredPropertyKey => $requiredProperty) {
                if (!property_exists($instance, $requiredProperty)) {
                    $context->pushSchema(keywordLocationFragment: (string)$requiredPropertyKey);

                    $context->createResultForKeyword($this, $keywordValue)->invalidate(
                        'Dependent required property \''
                        . $requiredProperty
                        . '\' is missing'
                    );

                    $context->popSchema();

                    $result->invalidate();

                    if ($context->draft->shortCircuit()) {
                        break 2;
                    }
                }
            }
        }

        return $result;
    }
}