<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Validation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class ContentMediaTypeKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    protected const PATTERN_MIME_TYPE_FORMAT = <<<'REGEX'
/[a-z0-9!#\$%\^&\*_\-\+\{\}\|'\.`~]+\/[a-z0-9!#\$%\^&\*_\-\+\{\}\|'\.`~]+/i
REGEX;

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

        if (preg_match(static::PATTERN_MIME_TYPE_FORMAT, $keywordValue) === 0) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a media type, as defined by RFC 2046',
                $this,
                $context
            );
        }

        $keywordValue = strtolower($keywordValue);
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $instance = $context->getInstance();
        if (!is_string($instance)) {
            return null;
        }

        $result = $context->createResultForKeyword($this);

        if ($context->getConfig()->getAssertContentMediaTypeEncoding()) {
            $stream = fopen('php://memory','r+');
            fwrite($stream, $instance);
            rewind($stream);

            $mimeType = $this->detectMimeType($stream);

            if ($mimeType !== $keywordValue) {
                $result->setError(
                    'Mime type '
                    . $keywordValue
                    . ' expected, but is '
                    . $mimeType
                );
            }

            fclose($stream);
        }

        return $result;
    }

    /**
     * @param resource $stream
     * @return string
     */
    protected function detectMimeType($stream): string
    {
        return mime_content_type($stream) ?: 'application/octet-stream';
    }
}