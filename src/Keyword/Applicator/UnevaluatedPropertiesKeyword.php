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

        $relevantResultGroups = [
            $context->getResultsByKeywordName('properties'),
            $context->getResultsByKeywordName('patternProperties'),
            $context->getResultsByKeywordName('additionalProperties'),
            $context->getResultsByKeywordName('unevaluatedProperties'),
        ];

        $result = $context->createResultForKeyword($this);
        $evaluatedPropertyNames = [];

        foreach (get_object_vars($instance) as $propertyName => &$propertyValue) {
            foreach ($relevantResultGroups as $relevantResultGroup) {
                foreach ($relevantResultGroup as $relevantResult) {
                    $relevantResultAnnotation = $relevantResult->getAnnotation();
                    if (is_array($relevantResultAnnotation) && isset($relevantResultAnnotation[$propertyName])) {
                        continue 3;
                    }
                }
            }

            /** @noinspection DuplicatedCode */
            $context->pushSchema($keywordValue);
            $context->pushInstance($propertyValue, (string)$propertyName);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }

            $evaluatedPropertyNames[$propertyName] = $propertyName;
        }

        $result->setAnnotation($evaluatedPropertyNames);

        return $result;
    }
}