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
    protected const PATTERN_XML_NC_NAME_US_ASCII = '/[A-Z_][A-Z_0-9\-.]*$/i';

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
                'The value of "%s" must be a string',
                $this,
                $context
            );
        }

        if (preg_match(static::PATTERN_XML_NC_NAME_US_ASCII, $keywordValue) !== 1) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must start with a letter ([A-Za-z]) or underscore ("_"), followed by any number of'
                . ' letters, digits ([0-9]), hyphens ("-"), underscores ("_"), and periods (".")',
                $this,
                $context
            );
        }

        $anchorUri = $context->draft->createUri($context->getCurrentBaseUri())->withFragment($keywordValue);

        if ($context->hasSchema((string) $anchorUri)) {
            throw new StaticKeywordAnalysisException(
                'The value "' . $keywordValue . '" of "%s" is defined twice, but must be unique in each JSON Schema',
                $this,
                $context
            );
        }

        $context->registerSchema(
            (string) $anchorUri,
            $context->getCurrentSchema(),
            $context->getCurrentSchemaKeywordLocation(-1)
        );

        $keywordValue = (string) $anchorUri;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        return $context->createResultForKeyword($this);
    }
}