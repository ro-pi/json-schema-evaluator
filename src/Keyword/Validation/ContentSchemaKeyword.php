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
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!$keywordValue instanceof \stdClass && !is_bool($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a valid JSON Schema.',
                $this,
                $context
            );
        }

        $context->pushSchema($keywordValue);
        $context->draft->evaluateStatic($context);
        $context->popSchema();
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var \stdClass|bool $keywordValue */

        $instance = $context->getCurrentInstance();
        if (!is_string($instance)) {
            return null;
        }

        $contentMediaType = $context->getCurrentSchema()->{'contentMediaType'} ?? null;
        if (!$contentMediaType) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($context->draft->evaluateMutations()) {
            if ($this->shouldParseInstance($contentMediaType)) {
                $parseError = null;

                /* @phpstan-ignore-next-line */
                set_error_handler(static function(int $severity, string $error) use(&$parseError) {
                    $parseError = $error;
                });

                $instance = $this->parseInstance($context);

                restore_error_handler();

                if ($instance === null) {
                    $result->invalidate('Parsing of JSON failed.', $parseError);
                }
            }

            $context->pushSchema($keywordValue);
            $context->pushInstance($instance);

            $result->valid = $context->draft->evaluate($context);

            $context->popInstance();
            $context->popSchema();
        }

        return $result;
    }

    private function shouldParseInstance(string $contentMediaType): bool
    {
        return str_starts_with($contentMediaType, 'application/json');
    }

    /**
     * @return \stdClass|array<scalar, mixed>|null
     */
    private function parseInstance(RuntimeEvaluationContext $context): \stdClass|array|null
    {
        /** @var string $instance */
        $instance = $context->getCurrentInstance();
        $flags = $context->draft->acceptNumericStrings() ? JSON_BIGINT_AS_STRING : 0;

        $parsed = json_decode($instance, false, 512, $flags);

        if (!$parsed instanceof \stdClass && !is_array($parsed)) {
            return null;
        }

        return $parsed;
    }
}