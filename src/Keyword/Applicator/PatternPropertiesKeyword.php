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

class PatternPropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'patternProperties';
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

        foreach ($keywordValue as $pattern => $patternPropertySchema) {
            $context->pushSchema(keywordLocationFragment: (string) $pattern);

            if (!is_object($patternPropertySchema) && !is_bool($patternPropertySchema)) {
                throw new InvalidKeywordValueException(
                    'The property values of "%s" must valid JSON Schemas',
                    $this,
                    $context
                );
            }

            $context->setCurrentSchema($patternPropertySchema);
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
        $matchedPropertyNames = [];

        foreach ($instance as $propertyName => &$propertyValue) {
            foreach ($keywordValue as $pattern => $patternPropertySchema) {
                $numMatches = preg_match('{' . $pattern . '}u', $propertyName);
                if (!is_int($numMatches)) {
                    // Fail silently, because valid regular expressions are not a must have
                    // @see https://json-schema.org/draft/2020-12/json-schema-core.html#rfc.section.10.3.2.2
                    continue;
                }

                if ($numMatches <= 0) {
                    continue;
                }

                $context->pushSchema(schema: $patternPropertySchema, keywordLocationFragment: (string) $pattern);
                $context->pushInstance($propertyValue, (string) $propertyName);

                if (!$context->draft->evaluate($context)) {
                    $result->setValid(false);
                }

                $context->popInstance();
                $context->popSchema();

                $matchedPropertyNames[$propertyName] = $propertyName;
            }
        }

        if ($result->getValid()) {
            $result->setAnnotation($matchedPropertyNames);
        }

        return $result;
    }
}