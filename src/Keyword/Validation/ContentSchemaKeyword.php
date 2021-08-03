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

class ContentSchemaKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return 'contentSchema';
    }

    /**
     * @throws InvalidKeywordValueException
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue) && !is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid JSON Schema',
                $this,
                $context
            );
        }

        $context->pushSchema($keywordValue);
        $context->getDraft()->evaluateStatic($context);
        $context->popSchema();
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getCurrentInstance();
        if (!is_string($instance)) {
            return null;
        }

        $contentMediaType = $context->getCurrentSchema()->{'contentMediaType'} ?? null;
        if (!$contentMediaType) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($context->config->evaluateMutations) {
            if ($this->shouldParseInstance($contentMediaType)) {
                $parseError = null;
                set_error_handler(static function(int $severity, string $error) use(&$parseError) {
                    $parseError = $error;
                });

                $instance = $this->parseInstance($context);

                restore_error_handler();

                if ($instance === null) {
                    $result->setError('Parsing JSON failed', $parseError);
                }
            }

            $context->pushSchema($keywordValue);
            $context->pushInstance($instance);

            if (!$context->getDraft()->evaluate($context)) {
                $result->setValid(false);
            }

            $context->popInstance();
            $context->popSchema();
        }

        return $result;
    }

    protected function shouldParseInstance(string $contentMediaType): bool
    {
        return str_starts_with($contentMediaType, 'application/json');
    }

    protected function parseInstance(RuntimeEvaluationContext $context): object|array|null
    {
        $flags = $context->staticEvaluationContext->config->acceptNumericStrings
                 ? JSON_BIGINT_AS_STRING
                 : 0;

        $parsed = json_decode($context->getCurrentInstance(), false, 512, $flags);

        if (!is_object($parsed) && !is_array($parsed)) {
            return null;
        }

        return $parsed;
    }
}