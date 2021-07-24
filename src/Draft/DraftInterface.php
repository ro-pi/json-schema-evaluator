<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft;

use Psr\Http\Message\UriInterface;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;
use Ropi\JsonSchemaEvaluator\Type\BigNumberInterface;

interface DraftInterface
{
    function registerKeyword(KeywordInterface $keyword): void;
    function getKeywordByName(string $name): KeywordInterface;
    function schemaHasMutationKeywords(object|bool $schema): bool;

    function supportsVocabulary(string $vocabulary): bool;
    function getVocabularies(): array;

    /**
     * @throws InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    function evaluateStatic(StaticEvaluationContext $context): void;
    function evaluate(RuntimeEvaluationContext $context, bool $mutationsOnly = false): bool;
    function getUri(): string;

    function resolveUri(UriInterface|string $baseUri, UriInterface|string $uri): UriInterface;
    function createUri(string $uri): ?UriInterface;

    function dereferenceJsonPointer(object $schema, string $fragment): mixed;

    function createBigNumber(mixed $value, bool $acceptNumericStrings = false): ?BigNumberInterface;
    function valueIsNumeric(mixed $value): bool;
    function valuesAreEqual(mixed $value1, mixed $value2): bool;
}