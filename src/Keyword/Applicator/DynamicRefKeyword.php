<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;

class DynamicRefKeyword extends RefKeyword
{
    public function getName(): string
    {
        return '$dynamicRef';
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var string $keywordValue */

        $hasBookend  = $context->staticEvaluationContext->hasDynamicAnchorUri($keywordValue);

        if ($hasBookend) {
            $uriComponents = explode('#', $keywordValue, 2);
            $fragment = $uriComponents[1] ?? '';

            $mostOuterDynamicAnchorUri = $context->getMostOuterDynamicAnchorUri($fragment);
            if ($mostOuterDynamicAnchorUri) {
                $keywordValue = $mostOuterDynamicAnchorUri;
            }
        }

        return parent::evaluate($keywordValue, $context);
    }
}