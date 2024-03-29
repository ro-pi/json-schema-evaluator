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

class ContainsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'contains';
    }

    /**
     * @throws StaticKeywordAnalysisException
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
        if (!is_array($instance)) {
            return null;
        }

        $currentSchema = $context->getCurrentSchema();
        $result = $context->createResultForKeyword($this, $keywordValue);

        $matchedIndexes = [];

        $intermediateContext = clone $context;

        foreach ($instance as $instanceIndex => &$instanceValue) {
            $intermediateContext->pushSchema($keywordValue);
            $intermediateContext->pushInstance($instanceValue, (string)$instanceIndex);

            $valid = $context->draft->evaluate($intermediateContext);

            $intermediateContext->popInstance();
            $intermediateContext->popSchema();

            if ($valid) {
                $matchedIndexes[$instanceIndex] = $instanceIndex;
            }
        }

        $context->adoptResultsFromContextAsAnnotations($intermediateContext);

        if ($matchedIndexes) {
            ksort($matchedIndexes);
        } elseif (!isset($currentSchema->minContains) || $currentSchema->minContains > 0) {
            $result->invalidate('No element matches schema');
            return $result;
        }

        $result->setAnnotationValue((count($matchedIndexes) === count($instance)) ?: $matchedIndexes);

        return $result;
    }
}