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
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be an object',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $dependencyPropertyName => $requiredProperties) {
            $context->pushSchema(keywordLocationFragment: (string) $dependencyPropertyName);

            if (!is_array($requiredProperties)) {
                throw new InvalidKeywordValueException(
                    'The property "'
                    . $dependencyPropertyName
                    . '" in "%s" object must be an array',
                    $this,
                    $context
                );
            }

            foreach ($requiredProperties as $requiredPropertyKey => $requiredProperty) {
                $context->pushSchema(keywordLocationFragment: (string) $requiredPropertyKey);

                if (!is_string($requiredProperty)) {
                    throw new InvalidKeywordValueException(
                        'The array elements of property "'
                        . $dependencyPropertyName
                        . '" in "%s" object must be strings',
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
        $instance = $context->getInstance();
        if (!is_object($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        $shortCircuit = $context->getConfig()->getShortCircuit();
        $missingProperties = [];

        foreach ($keywordValue as $dependencyPropertyName => $requiredProperties) {
            if (!property_exists($instance, $dependencyPropertyName)) {
                continue;
            }

            foreach ($requiredProperties as $requiredProperty) {
                if (!property_exists($instance, $requiredProperty)) {
                    if ($shortCircuit) {
                        $result->setError(
                            'Dependent required property '
                            . $requiredProperty
                            . ' is missing'
                        );

                        return $result;
                    }

                    $missingProperties[] = $requiredProperty;
                }
            }
        }

        if ($missingProperties) {
            $result->setError(
                'Missing dependent required properties: '
                . implode(', ', $missingProperties),
                $missingProperties
            );
        }

        return $result;
    }
}