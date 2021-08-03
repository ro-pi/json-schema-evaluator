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

class ThenKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'then';
    }

    /**
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
        $ifResult = $context->getLastResultByKeywordLocation($context->getCurrentKeywordLocation(-1) . '/if');
        if (!$ifResult || !$ifResult->getEvaluationResult()) {
            return null;
        }

        $result = $context->createResultForKeyword($this);
        $context->pushSchema($keywordValue);

        $result->valid = $context->draft->evaluate($context);

        $context->popSchema();

        return $result;
    }
}