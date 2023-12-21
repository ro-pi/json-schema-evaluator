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

    /**
     * @var KeywordInterface[][]
     */
    private array $keywordsByVocabulary = [];

    /**
     * @var bool[]
     */
    protected array $vocabularies = [];

    public function __construct(
        private readonly string $uri = '',
        private readonly bool $assertFormat = false,
        private readonly bool $assertContentMediaTypeEncoding = false,
        private readonly bool $evaluateMutations = false,
        private readonly bool $acceptNumericStrings = false,
        private readonly bool $shortCircuit = false
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
                'Can not enable vocabulary \''
                . $vocabulary
                . '\', because vocabulary is not supported.',
                1647637917
            );
        }

        return $this->vocabularies[$vocabulary];
    }

    public function enableVocabulary(string $vocabulary): void
    {
        if (!$this->supportsVocabulary($vocabulary)) {
            throw new UnsupportedVocabularyException(
                'Can not enable vocabulary \''
                . $vocabulary
                . '\', because vocabulary is not supported.',
                1647637758
            );
        }

        if ($this->vocabularies[$vocabulary]) {
            return;
        }

        $this->vocabularies[$vocabulary] = true;
        $this->registerKeywords();
    }

    public function disableVocabulary(string $vocabulary): void
    {
        if (!$this->supportsVocabulary($vocabulary)) {
            throw new UnsupportedVocabularyException(
                'Can not disable vocabulary \''
                . $vocabulary
                . '\', because vocabulary is not supported.',
                1647637759
            );
        }

        if (!$this->vocabularies[$vocabulary]) {
            return;
        }

        $this->vocabularies[$vocabulary] = false;
        $this->registerKeywords();
    }

    abstract protected function registerKeywords(): void;

    public function registerKeyword(KeywordInterface $keyword, string $vocabulary): void
    {
        $this->keywordsByVocabulary[$vocabulary][$keyword->getName()] = $keyword;
        $this->keywords[$keyword->getName()] = $keyword;
    }

    protected function unregisterKeywordByVocabulary(string $vocabulary): void
    {
        foreach ($this->keywordsByVocabulary[$vocabulary] as $keyword) {
            unset($this->keywords[$keyword->getName()]);
        }

        $this->keywordsByVocabulary[$vocabulary] = [];
    }

    public function getKeywordByName(string $name): KeywordInterface
    {
        return $this->keywords[$name] ?? new UnknownKeyword(1647650000, $name);
    }

    public function schemaHasMutationKeywords(\stdClass|bool $schema): bool
    {
        if (!$schema instanceof \stdClass) {
            return false;
        }

        foreach (get_object_vars($schema) as $keywordName => $keywordValue) {
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

        if (!$schema instanceof \stdClass) {
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
            if ($schema === false) {
                $lastResult = $context->getLastResult();
                if ($lastResult) {
                    $context->createResultForKeyword($lastResult->keyword)->invalidate('Not allowed');
                }
            }

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
            $baseUri = UriNormalizer::normalize(new Uri($baseUri));
        }

        if (!$uri instanceof UriInterface) {
            $uri = UriNormalizer::normalize(new Uri($uri));
        }

        return UriNormalizer::normalize(UriResolver::resolve($baseUri, $uri));
    }

    public function tryCreateUri(string $uri): ?UriInterface
    {
        try {
            return UriNormalizer::normalize(new Uri($uri));
        } catch (\InvalidArgumentException) {
            // Fail silently
        }

        return null;
    }

    public function dereferenceJsonPointer(\stdClass $schema, string $fragment): mixed
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
            } elseif ($currentSchemaPart instanceof \stdClass) {
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

    public function tryCreateNumber(mixed $value): ?NumberInterface
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
            /* @phpstan-ignore-next-line */
            return $this->tryCreateNumber($value1)->equals($this->tryCreateNumber($value2));
        }

        if (gettype($value1) !== gettype($value2)) {
            return false;
        }

        if (is_array($value1) && is_array($value2)) {
            foreach ($value1 as $key => $value) {
                if (!array_key_exists($key, $value2)) {
                    return false;
                }

                if (!$this->valuesAreEqual($value, $value2[$key])) {
                    return false;
                }
            }

            foreach ($value2 as $key => $value) {
                if (!array_key_exists($key, $value1)) {
                    return false;
                }
            }

            return true;
        }

        if ($value1 instanceof \stdClass && $value2 instanceof \stdClass) {
            foreach (get_object_vars($value1) as $propertyName => $propertyValue) {
                if (!property_exists($value2, $propertyName)) {
                    return false;
                }

                if (!$this->valuesAreEqual($propertyValue, $value2->$propertyName)) {
                    return false;
                }
            }

            foreach (get_object_vars($value2) as $key => $value) {
                if (!property_exists($value1, $key)) {
                    return false;
                }
            }

            return true;
        }

        return $value1 === $value2;
    }

    /**
     * @return KeywordInterface[]
     */
    protected function prioritizeSchemaKeywords(\stdClass $schema, StaticEvaluationContext $context): array
    {
        $prioritizedKeywords = [];

        foreach (get_object_vars($schema) as $keywordName => $keywordValue) {
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