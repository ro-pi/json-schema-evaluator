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

class PropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'properties';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be an object.',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $propertyName => $propertySchema) {
            $context->pushSchema(keywordLocationFragment: (string) $propertyName);

            if (!is_object($propertySchema) && !is_bool($propertySchema)) {
                throw new InvalidKeywordValueException(
                    'Property \''
                    . $propertyName
                    . '\' of \'%s\' object must be a valid JSON Schema.',
                    $this,
                    $context
                );
            }

            $context->setCurrentSchema($propertySchema);
            $context->draft->evaluateStatic($context);

            $context->popSchema();
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_object($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        $evaluatedProperties = [];

        foreach ($keywordValue as $propertyName => $propertySchema) {
            $propertyExists = property_exists($instance, $propertyName);

            if (!$propertyExists) {
                if (!$context->draft->evaluateMutations()) {
                    continue;
                }

                if (!$context->draft->schemaHasMutationKeywords($propertySchema)) {
                    continue;
                }

                $instance->$propertyName = null;
            }

            $context->pushSchema(schema: $propertySchema, keywordLocationFragment: (string) $propertyName);
            $context->pushInstance($instance->$propertyName, (string) $propertyName);

            if ($propertyExists) {
                $valid = $context->draft->evaluate($context);
            } else {
                $valid = $context->draft->evaluate($context, true);
            }

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }

            $evaluatedProperties[$propertyName] = $propertyName;
        }

        if ($result->valid) {
            $result->setAnnotation($evaluatedProperties);
        }

        return $result;
    }
}