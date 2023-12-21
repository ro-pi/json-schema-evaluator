<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class DefsKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    public function getName(): string
    {
        return '$defs';
    }

    /**
     * @throws InvalidKeywordValueException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
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

        foreach (get_object_vars($keywordValue) as $schemaIdentifier => $schema) {
            $context->pushSchema(keywordLocationFragment: (string)$schemaIdentifier);

            if (!$schema instanceof \stdClass && !is_bool($schema)) {
                throw new InvalidKeywordValueException(
                    'Each member of \'%s\' must be a valid JSON Schema.',
                    $this,
                    $context
                );
            }

            $context->setCurrentSchema($schema);
            $context->draft->evaluateStatic($context);

            $context->popSchema();
        }
    }
}