<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class PropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface
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
                'The value of "%s" must be an object',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $propertyName => $propertySchema) {
            $context->pushSchema(keywordLocationFragment: (string) $propertyName);

            if (!is_object($propertySchema) && !is_bool($propertySchema)) {
                throw new InvalidKeywordValueException(
                    'Property "'
                    . $propertyName
                    . '" of "%s" object must be a valid JSON Schema',
                    $this,
                    $context
                );
            }

            $context->setSchema($propertySchema);
            $context->getDraft()->evaluateStatic($context);

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

        $evaluatedProperties = [];

        foreach ($keywordValue as $propertyName => $propertySchema) {
            $propertyExists = property_exists($instance, $propertyName);

            if (!$propertyExists) {
                if (!$context->getConfig()->getEvaluateMutations()) {
                    continue;
                }

                if (!$context->getDraft()->schemaHasMutationKeywords($propertySchema)) {
                    continue;
                }

                $instance->$propertyName = null;
            }

            $context->pushSchema(schema: $propertySchema, keywordLocationFragment: (string) $propertyName);
            $context->pushInstance($instance->$propertyName, (string) $propertyName);

            if ($propertyExists) {
                if (!$context->getDraft()->evaluate($context)) {
                    $result->setValid(false);
                }
            } else {
                $context->getDraft()->evaluate(clone $context, true);
            }

            $context->popInstance();
            $context->popSchema();

            $evaluatedProperties[$propertyName] = $propertyName;
        }

        if ($result->getValid()) {
            $result->setAnnotation($evaluatedProperties);
        }

        return $result;
    }
}