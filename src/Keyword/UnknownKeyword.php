<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;

class UnknownKeyword extends AbstractKeyword implements KeywordInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /*public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (is_object($keywordValue)) {
            $this->evaluateStaticObject($keywordValue, $context);
        } else if (is_array($keywordValue)) {
            $this->evaluateStaticArray($keywordValue, $context);
        }
    }*/

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $result = $context->createResultForKeyword($this);
        $result->setAnnotation($keywordValue);

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /*
    protected function evaluateStaticObject(object &$keywordValue, $context): void
    {
        $context->pushSchema($keywordValue);
        $context->getDraft()->evaluateStatic($context);
        $context->popSchema();
    }

    protected function evaluateStaticArray(array &$keywordValue, $context): void
    {
        foreach ($keywordValue as &$item) {
            if (is_object($item)) {
                $this->evaluateStaticObject($item, $context);
            } else if (is_array($item)) {
                $this->evaluateStaticArray($keywordValue, $context);
            }
        }
    }
    */
}