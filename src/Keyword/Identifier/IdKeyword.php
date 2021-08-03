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

class IdKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    public function getName(): string
    {
        return '$id';
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

        $uri = $context->getDraft()->createUri($keywordValue);
        if (!$uri) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid URI reference',
                $this,
                $context
            );
        }

        if ($uri->getFragment()) {
            throw new InvalidKeywordValueException(
                'The URI reference of "%s" must not contain a non-empty fragment',
                $this,
                $context
            );
        }

        $resolvedUri = $context->getDraft()->resolveUri($context->getCurrentBaseUri(), $uri);
        $normalizedUri = $resolvedUri->withFragment('');

        if ($context->hasSchema((string) $normalizedUri)) {
            throw new StaticKeywordAnalysisException(
                'The URI reference "' . $keywordValue . '" is defined twice',
                $this,
                $context
            );
        }

        $context->registerSchema(
            (string) $normalizedUri,
            $context->getCurrentSchema(),
            $context->getCurrentSchemaKeywordLocation(-1)
        );

        $context->setCurrentBaseUri((string) $normalizedUri);
        $context->setCurrentBaseUri((string) $normalizedUri, -1);

        $keywordValue = (string) $normalizedUri;
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $context->setCurrentBaseUri($keywordValue);
        $context->setCurrentBaseUri($keywordValue, -1);

        return $context->createResultForKeyword($this);
    }
}