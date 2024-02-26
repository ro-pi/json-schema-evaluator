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
     */
    public function evaluateStaticOf(mixed $keywordValue, StaticKeywordInterface $keyword, StaticEvaluationContext $context): void
    {
        if (!is_array($keywordValue) || !$keywordValue) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a non-empty array.',
                $keyword,
                $context
            );
        }

        foreach ($keywordValue as $schemaKey => $schema) {
           $context->pushSchema(keywordLocationFragment: (string)$schemaKey);

            if (!$schema instanceof \stdClass && !is_bool($schema)) {
                throw new InvalidKeywordValueException(
                    'The array elements of \'%s\' must be valid JSON Schemas.',
                    $keyword,
                    $context
                );
            }

            $context->setCurrentSchema($schema);
            $context->draft->evaluateStatic($context);

            $context->popSchema();
        }
    }

    /**
     * @param list<\stdClass|bool> $keywordValue
     */
    public function evaluateOf(array $keywordValue, RuntimeEvaluationContext $context): int
    {
        $numMatches = 0;

        foreach ($keywordValue as $ofSchemaKey => $ofSchema) {
            // Clone context without results (@see RuntimeEvaluationContext::__clone()),
            // to avoid accessing results of cousins
            $intermediateContext = clone $context;
            $intermediateContext->pushSchema(schema: $ofSchema, keywordLocationFragment: (string)$ofSchemaKey);

            if ($context->draft->evaluate($intermediateContext)) {
                $numMatches++;
            }

            $context->adoptResultsFromContextAsAnnotations($intermediateContext);
        }

        return $numMatches;
    }
}