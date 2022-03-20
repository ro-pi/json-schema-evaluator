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

class UnevaluatedItemsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'unevaluatedItems';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue) && !is_bool($keywordValue)) {
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
        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $itemsResults = $context->getResultsByKeywordName('items');
        foreach ($itemsResults as $itemsResult) {
            if ($itemsResult->getAnnotation() === true) {
                return null;
            }
        }

        $containsResults = $context->getResultsByKeywordName('contains');
        foreach ($containsResults as $containsResult) {
            if ($containsResult->getAnnotation() === true) {
                return null;
            }
        }

        $unevaluatedItemsResults = $context->getResultsByKeywordName('unevaluatedItems');
        foreach ($unevaluatedItemsResults as $unevaluatedItemsResult) {
            if ($unevaluatedItemsResult->getAnnotation() === true) {
                return null;
            }
        }

        $startIndex = -1;

        $prefixItemsResults = $context->getResultsByKeywordName('prefixItems');
        foreach ($prefixItemsResults as $prefixItemsResult) {
            $annotation = $prefixItemsResult->getAnnotation();
            if ($annotation === true) {
                return null;
            }

            if (is_int($annotation) && $annotation > $startIndex) {
                $startIndex = $annotation;
            }
        }

        $result = $context->createResultForKeyword($this);

        for ($instanceIndex = ++$startIndex; $instanceIndex < count($instance); $instanceIndex++) {
            foreach ($containsResults as $containsResult) {
                $containsAnnotation = $containsResult->getAnnotation();
                if (is_array($containsAnnotation) && isset($containsAnnotation[$instanceIndex])) {
                    continue 2;
                }
            }

            $context->pushSchema($keywordValue);
            $context->pushInstance($instance[$instanceIndex], (string) $instanceIndex);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }
        }

        $result->setAnnotation(true);

        return $result;
    }
}