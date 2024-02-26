<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\MutationKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class ContentEncodingKeyword extends AbstractKeyword implements StaticKeywordInterface, MutationKeywordInterface
{
    public function getName(): string
    {
        return 'contentEncoding';
    }

    /**
     * @throws StaticKeywordAnalysisException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_string($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a string.',
                $this,
                $context
            );
        }

        $keywordValue = strtolower($keywordValue);

        if (!$this->getDecoderCallableForEncoding($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a valid supported encoding. Following encodings are supported:'
                . implode(', ', $this->getSupportedEncodings())
                . '.',
                $this,
                $context
            );
        }
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var string $keywordValue */

        $instance =& $context->getCurrentInstance();
        if (!is_string($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this, $keywordValue);

        if ($context->draft->evaluateMutations()) {
            /** @var callable $decodingCallable */
            $decodingCallable = $this->getDecoderCallableForEncoding($keywordValue);

            $decodingError = null;

            /* @phpstan-ignore-next-line */
            set_error_handler(static function(int $severity, string $error) use(&$decodingError) {
                $decodingError = $error;
            });

            $decoded = $decodingCallable($instance);

            restore_error_handler();

            if (is_string($decoded)) {
                $instance = $decoded;
            } else {
                $result->invalidate(
                    $keywordValue . ' decoding failed',
                    $decodingError
                );
            }
        }

        return $result;
    }

    private function getDecoderCallableForEncoding(string $encoding): ?callable
    {
        $methodName = 'decode'. ucfirst($encoding);
        if (!method_exists($this, $methodName)) {
            return null;
        }

        return $this->$methodName(...);
    }

    /**
     * @return list<string>
     */
    private function getSupportedEncodings(): array
    {
        $methods = get_class_methods($this);

        $supportedEncodings = [];

        foreach ($methods as $method) {
            if (str_starts_with($method, 'decode')) {
                $supportedEncodings[] = ucfirst(substr($method, 6));
            }
        }

        return $supportedEncodings;
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function decodeBase64(string $instance): ?string
    {
        return base64_decode($instance) ?: null;
    }
}