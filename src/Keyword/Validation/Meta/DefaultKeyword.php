<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation\Meta;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\MutationKeywordInterface;

class DefaultKeyword extends AbstractKeyword implements MutationKeywordInterface
{
    public function getName(): string
    {
        return 'default';
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance =& $context->getInstance();
        if ($instance === null && $context->config->evaluateMutations) {
            $instance = $keywordValue;
        }

        return $context->createResultForKeyword($this);
    }
}