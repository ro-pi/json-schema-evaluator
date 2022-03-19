<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class VocabularyKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    public function getName(): string
    {
        return '$vocabulary';
    }

    /**
     * @throws InvalidKeywordValueException
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_object($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be an object',
                $this,
                $context
            );
        }

        foreach ($keywordValue as $vocabulary => $required) {
            if ($required && !$context->draft->supportsVocabulary($vocabulary)) {
                throw new StaticKeywordAnalysisException(
                    'The value of "%s" indicates that the vocabulary "'
                    . $vocabulary
                    . '" is required, but that vocabulary is not supported by draft with URI "'
                    . $context->draft->getUri()
                    . '"',
                    $this,
                    $context
                );
            }
        }
    }
}