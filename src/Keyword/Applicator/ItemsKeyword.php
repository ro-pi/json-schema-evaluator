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

        $prefixItemsAnnotation = $context->getLastResultByKeywordName('prefixItems')?->getAnnotationValue();
        if ($prefixItemsAnnotation === true) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        $startIndex = is_int($prefixItemsAnnotation) ? $prefixItemsAnnotation : -1;

        for ($instanceIndex = ++$startIndex; $instanceIndex < count($instance); $instanceIndex++) {
            /** @noinspection DuplicatedCode */
            $context->pushSchema($keywordValue);
            $context->pushInstance($instance[$instanceIndex], (string)$instanceIndex);

            $valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();

            if (!$valid) {
                $result->invalidate();

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }
        }

        $result->setAnnotationValue(true);

        return $result;
    }
}