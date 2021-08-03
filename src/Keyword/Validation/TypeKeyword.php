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

class TypeKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public const SUPPORTED_TYPES = [
        'object' => 'object',
        'array' => 'array',
        'string' => 'string',
        'number' => 'number',
        'boolean' => 'boolean',
        'null' => 'null',
        'integer' => 'integer'
    ];

    public function getName(): string
    {
        return 'type';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_string($keywordValue) && !is_array($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a string or an array',
                $this,
                $context
            );
        }

        $types = is_array($keywordValue) ? $keywordValue : [$keywordValue];

        foreach ($types as $type) {
            if (!is_string($type)) {
                throw new InvalidKeywordValueException(
                    'The array elements of "%s" must be strings',
                    $this,
                    $context
                );
            }

            if (!isset(static::SUPPORTED_TYPES[$type])) {
                throw new InvalidKeywordValueException(
                    'The value of "%s" must be a valid type ('
                    . $this->arrayToHumanReadableList(static::SUPPORTED_TYPES)
                    . ') or an array of valid types',
                    $this,
                    $context
                );
            }
        }

        $keywordValue = $types;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        if (!$keywordValue) {
            //Ignore keyword also if empty or false (same as default behavior)
            return null;
        }

        $instanceType = $this->detectType($context->getCurrentInstance(), $context);

        $result = $context->createResultForKeyword($this);

        foreach ($keywordValue as $type) {
            if ($instanceType === $type) {
                return $result;
            }

            if ($type === 'number' && $instanceType === 'integer') {
                return $result;
            }
        }

        $result->setError(
            'Type '
            . $this->arrayToHumanReadableList($keywordValue)
            . ' expected, but is '
            . $instanceType
        );

        return $result;
    }

    protected function detectType(mixed $instance, RuntimeEvaluationContext $context): string
    {
        $acceptNumericStrings = $context->staticEvaluationContext->config->acceptNumericStrings;

        return match (true) {
            is_object($instance) => 'object',
            is_array($instance) => 'array',
            $context->draft->createBigNumber($instance, $acceptNumericStrings)?->isInteger() => 'integer',
            $context->draft->valueIsNumeric($instance) => 'number',
            is_string($instance) => 'string',
            is_bool($instance) => 'boolean',
            ($instance === null) => 'null',
            default => 'unknown'
        };
    }

    protected function arrayToHumanReadableList(array $array): string
    {
        if (count($array) <= 1) {
            return implode(', ', $array);
        }

        $lastElement = array_pop($array);
        $list = implode(', ', $array);

        if ($lastElement) {
            $list .= ' or ' . $lastElement;
        }

        return $list;
    }
}