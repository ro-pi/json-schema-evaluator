<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException;

interface KeywordInterface
{
    function getName(): string;
    function setPriority(int $priority): void;
    function hasPriority(): bool;
    function getPriority(): ?int;

    /**
     * @throws KeywordRuntimeEvaluationException
     */
    function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult;
}