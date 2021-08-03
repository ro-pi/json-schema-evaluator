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

class DependentSchemasKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'dependentSchemas';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
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

        foreach ($keywordValue as $dependencyPropertyName => $dependentSchema) {
            $context->pushSchema(keywordLocationFragment: (string) $dependencyPropertyName);

            if (!is_object($dependentSchema) && !is_bool($dependentSchema)) {
                throw new InvalidKeywordValueException(
                    'The property "'
                    . $dependencyPropertyName
                    . '" in "%s" object must be a valid JSON Schema',
                    $this,
                    $context
                );
            }

            $context->pushSchema($dependentSchema);
            $context->draft->evaluateStatic($context);
            $context->popSchema();

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

        foreach ($keywordValue as $dependencyPropertyName => $dependentSchema) {
            $propertyExists = property_exists($instance, $dependencyPropertyName);

            if (!$propertyExists) {
                if (!$context->config->evaluateMutations) {
                    continue;
                }

                if (!$context->draft->schemaHasMutationKeywords($dependentSchema)) {
                    continue;
                }

                $instance->$dependencyPropertyName = null;
            }

            $context->pushSchema(schema: $dependentSchema, keywordLocationFragment: (string) $dependencyPropertyName);

            if ($propertyExists) {
                $valid = $context->draft->evaluate($context);
            } else {
                $valid = $context->draft->evaluate($context, true);
            }

            $context->popSchema();

            if (!$valid) {
                $result->setValid(false);

                if ($context->config->shortCircuit) {
                    break;
                }
            }
        }

        return $result;
    }
}