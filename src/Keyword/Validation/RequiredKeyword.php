<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class RequiredKeyword extends AbstractKeyword implements StaticKeywordInterface
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
                'The value of "%s" must be an array',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $requiredPropertyKey => $requiredProperty) {
            $context->pushSchema(keywordLocationFragment: (string) $requiredPropertyKey);

            if (!is_string($requiredProperty)) {
                throw new InvalidKeywordValueException(
                    'The array elements of "%s" must be strings',
                    $this,
                    $context
                );
            }

            $context->popSchema();
        }

        if (!$keywordValue) {
            // Remove keyword if empty (same as default behavior)
            unset($context->getSchema()->{$this->getName()});
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

        foreach ($keywordValue as $requiredProperty) {
            if (!property_exists($instance, $requiredProperty)) {
                if ($shortCircuit) {
                    $result->setError(
                        'Required property '
                        . $requiredProperty
                        . ' is missing'
                    );

                    return $result;
                }

                $missingProperties[] = $requiredProperty;
            }
        }

        if ($missingProperties) {
            $result->setError(
                'Missing required properties: '
                . implode(', ', $missingProperties),
                $missingProperties
            );
        }

        return $result;
    }
}