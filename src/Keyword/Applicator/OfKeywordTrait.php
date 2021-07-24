<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

trait OfKeywordTrait
{
    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStaticOf(mixed $keywordValue, StaticKeywordInterface $keyword, StaticEvaluationContext $context): void
    {
        if (!is_array($keywordValue) || !$keywordValue) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a non-empty array',
                $keyword,
                $context
            );
        }

        foreach ($keywordValue as $schemaKey => $schema) {
           $context->pushSchema(keywordLocationFragment: (string) $schemaKey);

            if (!is_object($schema) && !is_bool($schema)) {
                throw new InvalidKeywordValueException(
                    'The array elements of "%s" must be valid JSON Schemas',
                    $keyword,
                    $context
                );
            }

            $context->setSchema($schema);
            $context->getDraft()->evaluateStatic($context);

            $context->popSchema();
        }
    }

    public function evaluateOf(array $keywordValue, RuntimeEvaluationContext $context): int
    {
        $numMatches = 0;

        foreach ($keywordValue as $ofSchemaKey => $ofSchema) {
            $intermediateContext = clone $context;
            $intermediateContext->pushSchema(schema: $ofSchema, keywordLocationFragment: (string) $ofSchemaKey);

            if ($context->getDraft()->evaluate($intermediateContext)) {
                $numMatches++;
            }

            $context->adoptResultsFromContext($intermediateContext);
        }

        return $numMatches;
    }
}