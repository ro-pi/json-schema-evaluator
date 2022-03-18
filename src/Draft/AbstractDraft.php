<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriNormalizer;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;
use Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\MutationKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\UnknownKeyword;
use Ropi\JsonSchemaEvaluator\Type\Number;
use Ropi\JsonSchemaEvaluator\Type\NumberInterface;

abstract class AbstractDraft implements DraftInterface
{
    /**
     * @var KeywordInterface[]
     */
    private array $keywords = [];
    private int $lastPriority = 0;

    /**
     * @var bool[]
     */
    protected array $vocabularies = [];

    public function __construct(
        private string $uri = '',
        private bool $assertFormat = false,
        private bool $assertContentMediaTypeEncoding = false,
        private bool $evaluateMutations = false,
        private bool $acceptNumericStrings = false,
        private bool $shortCircuit = false
    ) {}

    public function getUri(): string
    {
        return $this->uri;
    }

    public function assertFormat(): bool
    {
        return $this->assertFormat;
    }

    public function assertContentMediaTypeEncoding(): bool
    {
        return $this->assertContentMediaTypeEncoding;
    }

    public function evaluateMutations(): bool
    {
        return $this->evaluateMutations;
    }

    public function acceptNumericStrings(): bool
    {
        return $this->acceptNumericStrings;
    }

    public function shortCircuit(): bool
    {
        return $this->shortCircuit;
    }

    public function supportsVocabulary(string $vocabulary): bool
    {
        return isset($this->vocabularies[$vocabulary]);
    }

    public function getSupportedVocabularies(): array
    {
        return array_keys($this->vocabularies);
    }

    public function getVocabularies(): array
    {
        return $this->vocabularies;
    }

    public function vocabularyEnabled(string $vocabulary): bool
    {
        if (!$this->supportsVocabulary($vocabulary)) {
            throw new UnsupportedVocabularyException(
                'Can not enable vocabulary "'
                . $vocabulary
                . '", because vocabulary is not supported',
                1647637917
            );
        }

        return $this->vocabularies[$vocabulary];
    }

    public function enableVocabulary(string $vocabulary): void
    {
        if (!$this->supportsVocabulary($vocabulary)) {
            throw new UnsupportedVocabularyException(
                'Can not enable vocabulary "'
                . $vocabulary
                . '", because vocabulary is not supported',
                1647637758
            );
        }

        $this->vocabularies[$vocabulary] = true;
    }

    public function disableVocabulary(string $vocabulary): void
    {
        if (!$this->supportsVocabulary($vocabulary)) {
            throw new UnsupportedVocabularyException(
                'Can not disable vocabulary "'
                . $vocabulary
                . '", because vocabulary is not supported',
                1647637759
            );
        }

        $this->vocabularies[$vocabulary] = false;
    }

    public function registerKeyword(KeywordInterface $keyword): void
    {
        if (!$keyword->hasPriority()) {
            $keyword->setPriority($this->lastPriority += 1000);
        }

        $this->keywords[$keyword->getName()] = $keyword;
    }

    public function getKeywordByName(string $name): KeywordInterface
    {
        return $this->keywords[$name] ?? new UnknownKeyword($name);
    }

    public function schemaHasMutationKeywords(object|bool $schema): bool
    {
        if (!is_object($schema)) {
            return false;
        }

        foreach ($schema as $keywordName => $keywordValue) {
            if ($this->getKeywordByName($keywordName) instanceof MutationKeywordInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function evaluateStatic(StaticEvaluationContext $context): void
    {
        $schema = $context->getCurrentSchema();

        if (is_bool($schema)) {
            return;
        }

        if (!is_object($schema)) {
            throw new InvalidSchemaException(
                'JSON Schema must be an object or a boolean',
                $context
            );
        }

        if (!$context->hasPrioritizedSchemaKeywords($schema)) {
            $context->registerPrioritizedSchemaKeywords(
                $schema,
                $this->prioritizeSchemaKeywords($schema, $context)
            );
        }

        foreach ($context->getPrioritizedSchemaKeywords($schema) as $keyword) {
            if ($keyword instanceof StaticKeywordInterface) {
                $context->pushSchema(keywordLocationFragment: $keyword->getName());
                $keyword->evaluateStatic($schema->{$keyword->getName()}, $context);
                $context->popSchema();
            }
        }
    }

    /**
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException
     */
    public function evaluate(RuntimeEvaluationContext $context, bool $mutationsOnly = false): bool
    {
        $schema = $context->getCurrentSchema();

        if (is_bool($schema)) {
            return $schema;
        }

        $lastResultNumber = $context->getLastResultNumber();
        $shortCircuit = $context->draft->shortCircuit();
        $valid = true;

        foreach ($context->staticEvaluationContext->getPrioritizedSchemaKeywords($schema) as $keyword) {
            $name = $keyword->getName();

            if (
                !$keyword instanceof RuntimeKeywordInterface
                || ($mutationsOnly && !$keyword instanceof MutationKeywordInterface)
            ) {
                continue;
            }

            $context->pushSchema(keywordLocationFragment: $name);

            $evaluationResult = $keyword->evaluate($schema->{$name}, $context);
            if ($evaluationResult && !$evaluationResult->valid) {
                $valid = false;
            }

            $context->popSchema();

            if ($shortCircuit && !$valid) {
                break;
            }
        }

        if (!$valid) {
            // Annotations are not retained for failing schemas
            $context->suppressAnnotations($lastResultNumber);
        }

        return $valid;
    }

    public function resolveUri(UriInterface|string $baseUri, UriInterface|string $uri): UriInterface
    {
        if (!$baseUri instanceof UriInterface) {
            $baseUri = $this->createUri($baseUri);
        }

        if (!$uri instanceof UriInterface) {
            $uri = $this->createUri($uri);
        }

        return UriNormalizer::normalize(UriResolver::resolve($baseUri, $uri));
    }

    public function createUri(string $uri): ?UriInterface
    {
        try {
            return UriNormalizer::normalize(new Uri($uri));
        } catch (\InvalidArgumentException) {
            // Fail silently
        }

        return null;
    }

    public function dereferenceJsonPointer(object $schema, string $fragment): mixed
    {
        if (!$fragment) {
            return $schema;
        }

        $delimiter = '/';
        $currentSchemaPart = $schema;

        $tokens = explode($delimiter, ltrim($fragment, $delimiter));
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $this->decodeJsonPointerToken($tokens[$i]);

            if (is_array($currentSchemaPart)) {
                if (!isset($currentSchemaPart[$token])) {
                    return null;
                }

                $currentSchemaPart = &$currentSchemaPart[$token];
            } else if (is_object($currentSchemaPart)) {
                if (!isset($currentSchemaPart->{$token})) {
                    return null;
                }

                $currentSchemaPart = $currentSchemaPart->{$token};
            } else {
                return null;
            }
        }

        return $currentSchemaPart;
    }

    public function createNumber(mixed $value): ?NumberInterface
    {
        if ($value instanceof NumberInterface) {
            return clone $value;
        }

        if (!is_int($value) && !is_float($value)) {
            if ($this->acceptNumericStrings()) {
                if (!is_string($value) || !is_numeric($value)) {
                    return null;
                }
            } else {
                return null;
            }
        }

        try {
            return new Number(sprintf('%f', $value));
        } catch (\InvalidArgumentException) {
            // Instance is not a number
        }

        return null;
    }

    public function valueIsNumeric(mixed $value): bool
    {
        return is_int($value) || is_float($value) || $value instanceof NumberInterface;
    }

    public function valuesAreEqual(mixed $value1, mixed $value2): bool
    {
        if ($this->valueIsNumeric($value1) && $this->valueIsNumeric($value2)) {
            return $this->createNumber($value1)->equals($this->createNumber($value2));
        }

        if (gettype($value1) !== gettype($value2)) {
            return false;
        }

        $isArray = is_array($value1);
        if ($isArray || is_object($value1)) {
            foreach ($value1 as $key => $value) {
                if ($isArray) {
                    if (!array_key_exists($key, $value2)) {
                        return false;
                    }

                    if (!$this->valuesAreEqual($value, $value2[$key])) {
                        return false;
                    }
                } else {
                    if (!property_exists($value2, $key)) {
                        return false;
                    }

                    if (!$this->valuesAreEqual($value, $value2->$key)) {
                        return false;
                    }
                }
            }

            foreach ($value2 as $key => $value) {
                if ($isArray) {
                    if (!array_key_exists($key, $value1)) {
                        return false;
                    }
                } else if (!property_exists($value1, $key)) {
                    return false;
                }
            }

            return true;
        }

        return $value1 === $value2;
    }

    protected function prioritizeSchemaKeywords(object $schema, StaticEvaluationContext $context): array
    {
        /** @var StaticKeywordInterface[] $prioritizedKeywords */
        $prioritizedKeywords = [];

        foreach ($schema as $keywordName => $keywordValue) {
            $keyword = $context->draft->getKeywordByName($keywordName);
            $prioritizedKeywords[$keyword->getPriority()] = $keyword;
        }

        ksort($prioritizedKeywords);

        return $prioritizedKeywords;
    }

    protected function decodeJsonPointerToken(string $fragment): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], urldecode($fragment));
    }
}