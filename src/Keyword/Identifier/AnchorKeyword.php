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

class AnchorKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    private const PATTERN_XML_NC_NAME_US_ASCII = '/[A-Z_][A-Z_0-9\-.]*$/i';

    public function getName(): string
    {
        return '$anchor';
    }

    /**
     * @throws InvalidKeywordValueException
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

        if (preg_match(self::PATTERN_XML_NC_NAME_US_ASCII, $keywordValue) !== 1) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must start with a letter (a-z) or underscore (_), followed by any number of'
                . ' letters, digits (0-9), hyphens (-), underscores (_), and periods (.).',
                $this,
                $context
            );
        }

        $anchorUri = (string)$context->draft->tryCreateUri($context->getCurrentBaseUri())?->withFragment($keywordValue);

        if ($context->hasSchema($anchorUri)) {
            throw new StaticKeywordAnalysisException(
                'The value \'' . $keywordValue . '\' of \'%s\' is defined twice, but must be unique in each JSON Schema.',
                $this,
                $context
            );
        }

        /** @var \stdClass $currentSchema */
        $currentSchema = $context->getCurrentSchema();

        $context->registerSchema($anchorUri, $currentSchema, $context->getCurrentSchemaKeywordLocation(-1));

        $keywordValue = $anchorUri;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        return $context->createResultForKeyword($this);
    }
}