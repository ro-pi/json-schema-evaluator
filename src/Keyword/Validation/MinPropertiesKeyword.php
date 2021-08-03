<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class MinPropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    public function getName(): string
    {
        return 'minProperties';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_int($keywordValue) || $keywordValue < 0) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a non-negative integer',
                $this,
                $context
            );
        }

        if (!$keywordValue) {
            // Remove keyword if 0 (same as default behavior)
            unset($context->getSchema()->{$this->getName()});
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getInstance();
        if (!is_object($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);
        $numProperties = count(get_object_vars($instance));

        if ($numProperties < $keywordValue) {
            $result->setError(
                $numProperties
                . ' properties found, but at least '
                . $keywordValue
                . ' are required'
            );

            return $result;
        }

        return $result;
    }
}