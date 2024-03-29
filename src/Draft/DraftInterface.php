<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft;

use Psr\Http\Message\UriInterface;
use Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;
use Ropi\JsonSchemaEvaluator\Type\NumberInterface;

interface DraftInterface
{
    function registerKeyword(KeywordInterface $keyword, string $vocabulary): void;
    function getKeywordByName(string $name): KeywordInterface;

    function schemaHasMutationKeywords(\stdClass|bool $schema): bool;
    function supportsVocabulary(string $vocabulary): bool;

    /**
     * @return string[]
     */
    function getSupportedVocabularies(): array;

    /**
     * @return bool[]
     */
    function getVocabularies(): array;

    /**
     * @throws UnsupportedVocabularyException
     */
    function vocabularyEnabled(string $vocabulary): bool;

    /**
     * @throws UnsupportedVocabularyException
     */
    function enableVocabulary(string $vocabulary): void;

    /**
     * @throws UnsupportedVocabularyException
     */
    function disableVocabulary(string $vocabulary): void;

    function assertFormat(): bool;
    function evaluateMutations(): bool;
    function assertContentMediaTypeEncoding(): bool;
    function shortCircuit(): bool;
    function acceptNumericStrings(): bool;

    /**
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    function evaluateStatic(StaticEvaluationContext $context): void;
    function evaluate(RuntimeEvaluationContext $context, bool $mutationsOnly = false): bool;
    function getUri(): string;

    function resolveUri(UriInterface|string $baseUri, UriInterface|string $uri): UriInterface;
    function tryCreateUri(string $uri): ?UriInterface;

    function dereferenceJsonPointer(\stdClass $schema, string $fragment): mixed;

    function tryCreateNumber(mixed $value): ?NumberInterface;
    function valueIsNumeric(mixed $value): bool;
    function valuesAreEqual(mixed $value1, mixed $value2): bool;
}