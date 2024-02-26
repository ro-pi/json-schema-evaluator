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

class AdditionalPropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'additionalProperties';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!$keywordValue instanceof \stdClass && !is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a valid JSON Schema.',
                $this,
                $context
            );
        }

        $context->pushSchema($keywordValue);
        $context->draft->evaluateStatic($context);
        $context->popSchema();
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var \stdClass|bool $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!$instance instanceof \stdClass) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);
        $evaluatedPropertyNames = [];

        foreach ($context->getResultsByKeywordName('properties') as $propertiesResult) {
            $propertiesAnnotation = $propertiesResult->getAnnotationValue();
            if (!is_array($propertiesAnnotation)) {
                continue;
            }

            foreach ($propertiesAnnotation as $propertyName) {
                $evaluatedPropertyNames[$propertyName] = $propertyName;
            }
        }

        foreach ($context->getResultsByKeywordName('patternProperties') as $patternPropertiesResult) {
            $patternPropertiesAnnotation = $patternPropertiesResult->getAnnotationValue();
            if (!is_array($patternPropertiesAnnotation)) {
                continue;
            }

            foreach ($patternPropertiesAnnotation as $propertyName) {
                $evaluatedPropertyNames[$propertyName] = $propertyName;
            }
        }

        $additionalEvaluatedPropertyNames = [];

        foreach (get_object_vars($instance) as $propertyName => &$propertyValue) {
            if (isset($evaluatedPropertyNames[$propertyName])) {
                continue;
            }

            /** @noinspection DuplicatedCode */
            $context->pushSchema($keywordValue);
            $context->pushInstance($propertyValue, (string)$propertyName);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->invalidate();

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }

            $additionalEvaluatedPropertyNames[$propertyName] = $propertyName;
        }

        $result->setAnnotationValue($additionalEvaluatedPropertyNames);

        return $result;
    }
}