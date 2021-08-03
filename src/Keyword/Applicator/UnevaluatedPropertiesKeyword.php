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

class UnevaluatedPropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'unevaluatedProperties';
    }

    /**
     * @throws InvalidKeywordValueException
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue) && !is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid JSON Schema',
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
        $instance = $context->getCurrentInstance();
        if (!is_object($instance)) {
            return null;
        }

        $relevantResultGroups = [
            $context->getResultsByKeywordName('properties'),
            $context->getResultsByKeywordName('patternProperties'),
            $context->getResultsByKeywordName('additionalProperties'),
            $context->getResultsByKeywordName('unevaluatedProperties'),
        ];

        $result = $context->createResultForKeyword($this);
        $evaluatedPropertyNames = [];

        foreach ($instance as $propertyName => &$propertyValue) {
            foreach ($relevantResultGroups as $relevantResultGroup) {
                foreach ($relevantResultGroup as $relevantResult) {
                    $relevantResultAnnotation = $relevantResult->getAnnotation();
                    if (is_array($relevantResultAnnotation) && isset($relevantResultAnnotation[$propertyName])) {
                        continue 3;
                    }
                }
            }

            $context->pushSchema($keywordValue);
            $context->pushInstance($propertyValue, (string) $propertyName);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->setValid(false);

                if ($context->config->shortCircuit) {
                    break;
                }
            }

            $evaluatedPropertyNames[$propertyName] = $propertyName;
        }

        if ($result->getValid()) {
            $result->setAnnotation($evaluatedPropertyNames);
        }

        return $result;
    }
}