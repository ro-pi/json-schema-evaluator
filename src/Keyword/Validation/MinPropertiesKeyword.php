<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class MinPropertiesKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'minProperties';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_int($keywordValue) || $keywordValue < 0) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a non-negative integer.',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var int $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!$instance instanceof \stdClass || !$keywordValue) {
            // Ignore keyword also if 0 (same as default behavior)
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);
        $numProperties = count(get_object_vars($instance));

        if ($numProperties < $keywordValue) {
            $result->invalidate(
                $numProperties
                . ' properties found, but at least '
                . $keywordValue
                . ' are required',
                $numProperties
            );
        }

        return $result;
    }
}