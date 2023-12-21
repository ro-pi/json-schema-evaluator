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
                'The value of \'%s\' must be a string or an array.',
                $this,
                $context
            );
        }

        $types = is_array($keywordValue) ? $keywordValue : [$keywordValue];

        foreach ($types as $type) {
            if (!is_string($type)) {
                throw new InvalidKeywordValueException(
                    'The array elements of \'%s\' must be strings.',
                    $this,
                    $context
                );
            }

            if (!isset(static::SUPPORTED_TYPES[$type])) {
                throw new InvalidKeywordValueException(
                    'The value of \'%s\' must be a valid type ('
                    . $this->stringArrayToHumanReadableList(static::SUPPORTED_TYPES)
                    . ') or an array of valid types.',
                    $this,
                    $context
                );
            }
        }

        $keywordValue = $types;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var list<string> $keywordValue */

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

        $result->invalidate(
            'Type '
            . $this->stringArrayToHumanReadableList($keywordValue)
            . ' expected, but is '
            . $instanceType
            . '.'
        );

        return $result;
    }

    private function detectType(mixed $instance, RuntimeEvaluationContext $context): string
    {
        return match (true) {
            $instance instanceof \stdClass => 'object',
            is_array($instance) => 'array',
            $context->draft->tryCreateNumber($instance)?->isInteger() => 'integer',
            $context->draft->valueIsNumeric($instance) => 'number',
            is_string($instance) => 'string',
            is_bool($instance) => 'boolean',
            ($instance === null) => 'null',
            default => 'unknown'
        };
    }

    /**
     * @param string[] $stringArray
     * @return string
     */
    private function stringArrayToHumanReadableList(array $stringArray): string
    {
        if (count($stringArray) <= 1) {
            return implode(', ', $stringArray);
        }

        $lastElement = array_pop($stringArray);
        $list = implode(', ', $stringArray);

        if ($lastElement) {
            $list .= ' or ' . $lastElement;
        }

        return $list;
    }
}