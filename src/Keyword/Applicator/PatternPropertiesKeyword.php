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

        foreach (get_object_vars($keywordValue) as $pattern => $patternPropertySchema) {
            $context->pushSchema(keywordLocationFragment: (string)$pattern);

            if (!$patternPropertySchema instanceof \stdClass && !is_bool($patternPropertySchema)) {
                throw new InvalidKeywordValueException(
                    'The property values of \'%s\' must valid JSON Schemas.',
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
        /** @var \stdClass $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!$instance instanceof \stdClass) {
            return null;
        }

        $result = $context->createResultForKeyword($this);
        $matchedPropertyNames = [];

        foreach (get_object_vars($instance) as $propertyName => &$propertyValue) {
            foreach (get_object_vars($keywordValue) as $pattern => $patternPropertySchema) {
                /** @var \stdClass|bool $patternPropertySchema */

                $numMatches = preg_match('{' . $pattern . '}u', $propertyName);
                if (!is_int($numMatches)) {
                    // Fail silently, because valid regular expressions are not a must have
                    // @see https://json-schema.org/draft/2020-12/json-schema-core.html#rfc.section.10.3.2.2
                    continue;
                }

                if ($numMatches <= 0) {
                    continue;
                }

                $context->pushSchema(schema: $patternPropertySchema, keywordLocationFragment: (string)$pattern);
                $context->pushInstance($propertyValue, (string)$propertyName);

                $valid = $context->draft->evaluate($context);

                $context->popInstance();
                $context->popSchema();

                if (!$valid) {
                    $result->valid = false;

                    if ($context->draft->shortCircuit()) {
                        break 2;
                    }
                }

                $matchedPropertyNames[$propertyName] = $propertyName;
            }
        }

        $result->setAnnotation($matchedPropertyNames);

        return $result;
    }
}