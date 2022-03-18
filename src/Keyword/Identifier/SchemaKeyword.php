<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Identifier;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class SchemaKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return '$schema';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_string($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a string',
                $this,
                $context
            );
        }

        if (!filter_var($keywordValue, FILTER_VALIDATE_URL)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid URI reference',
                $this,
                $context
            );
        }

        $draft = $context->config->getSupportedDraftByUri($keywordValue);
        if (!$draft) {
            throw new StaticKeywordAnalysisException(
                'The dialect "'
                . $keywordValue
                . '" specified by "%s" is not supported',
                $this,
                $context
            );
        }

        $context->draft = $draft;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $draft = $context->staticEvaluationContext->config->getSupportedDraftByUri($keywordValue);

        if ($draft) {
            $context->draft = $draft;
        } else {
            throw new \RuntimeException(
                'The draft which was assigned to URI "'
                . $keywordValue
                . '" is no longer registered as supported draft',
                1647640409
            );
        }

        return $context->createResultForKeyword($this);
    }
}