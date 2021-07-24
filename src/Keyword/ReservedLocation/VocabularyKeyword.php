<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
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
     * @throws StaticKeywordAnalysisException
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
            if ($required && !$context->getDraft()->supportsVocabulary($vocabulary)) {
                throw new StaticKeywordAnalysisException(
                    'The value of "%s" indicates that the vocabulary "'
                    . $vocabulary
                    . '" is required, but that vocabulary is not supported by this implementation of draft "'
                    . $context->getDraft()->getUri()
                    . '"',
                    $this,
                    $context
                );
            }
        }

        // Remove vocabulary keyword from schema for faster evaluation
        unset($context->getSchema()->{$this->getName()});
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        return $context->createResultForKeyword($this);
    }
}