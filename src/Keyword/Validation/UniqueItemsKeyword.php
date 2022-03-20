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

class UniqueItemsKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'uniqueItems';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a boolean.',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_array($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if (!$keywordValue) {
            return $result;
        }

        $scalarItems = [];
        $complexItems = [];

        foreach ($instance as $instanceKey => $instanceValue) {
            if (is_array($instanceValue) || is_object($instanceValue)) {
                foreach ($complexItems as $complexItem) {
                    if ($context->draft->valuesAreEqual($instanceValue, $complexItem)) {
                        $context->pushInstance($instanceValue, (string) $instanceKey);
                        $context->createResultForKeyword($this)->invalidate('Item \'' . $instanceKey . '\' is not unique.');
                        $context->popInstance();

                        $result->valid = false;

                        if ($context->draft->shortCircuit()) {
                            break 2;
                        }
                    }
                }

                $complexItems[] = $instanceValue;

                continue;
            }

            $scalarKey = gettype($instanceValue) . '-' . $instanceValue;
            if (isset($scalarItems[$scalarKey])) {
                $context->pushInstance($instanceValue, (string) $instanceKey);
                $context->createResultForKeyword($this)->invalidate('Item \'' . $instanceKey . '\' is not unique.');
                $context->popInstance();

                $result->valid = false;

                if ($context->draft->shortCircuit()) {
                    break;
                }
            }

            $scalarItems[$scalarKey] = true;
        }

        return $result;
    }
}