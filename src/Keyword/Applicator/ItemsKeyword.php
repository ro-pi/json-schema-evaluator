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

class ItemsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'items';
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
        $context->getDraft()->evaluateStatic($context);
        $context->popSchema();
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $prefixItemsAnnotation = $context->getLastResultByKeywordName('prefixItems')?->getAnnotation();
        if ($prefixItemsAnnotation === true) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        $startIndex = is_int($prefixItemsAnnotation) ? $prefixItemsAnnotation : -1;

        for ($instanceIndex = ++$startIndex; $instanceIndex < count($instance); $instanceIndex++) {
            $context->pushSchema($keywordValue);
            $context->pushInstance($instance[$instanceIndex], (string) $instanceIndex);

            if (!$context->getDraft()->evaluate($context)) {
                $result->setValid(false);
            }

            $context->popInstance();
            $context->popSchema();
        }

        if ($result->getValid()) {
            $result->setAnnotation(true);
        }

        return $result;
    }
}