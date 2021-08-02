<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriNormalizer;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;
use Ropi\JsonSchemaEvaluator\Draft\Exception\DuplicateKeywordPriorityException;
use Ropi\JsonSchemaEvaluator\Draft\Exception\KeywordRegisterException;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\MutationKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\UnknownKeyword;
use Ropi\JsonSchemaEvaluator\Type\BigNumber;
use Ropi\JsonSchemaEvaluator\Type\BigNumberInterface;

abstract class AbstractDraft implements DraftInterface
{
    /**
     * @var KeywordInterface[]
     */
    private array $keywords = [];
    private int $priority = 0;

    /**
     * @var string[]
     */
    protected const VOCABULARIES = [];

    public function supportsVocabulary(string $vocabulary): bool
    {
        return isset(static::VOCABULARIES[$vocabulary]) && static::VOCABULARIES[$vocabulary];
    }

    public function getVocabularies(): array
    {
        return static::VOCABULARIES;
    }

    /**
     * @throws KeywordRegisterException
     */
    public function registerKeyword(KeywordInterface $keyword): void
    {
        if (!$keyword->hasPriority()) {
            $keyword->setPriority($this->priority += 1000);
        }

        foreach ($this->keywords as $registeredKeyword) {
            if ($registeredKeyword->getPriority() === $keyword->getPriority()) {
                throw new DuplicateKeywordPriorityException(
                    'Can not register keyword "'
                    . $keyword->getName()
                    . '", because it has the same priority as keyword "'
                    . $registeredKeyword->getPriority()
                    . '"',
                    1627924514
                );
            }
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
        $schema = $context->getSchema();

        if (is_bool($schema)) {
            return;
        }

        if (!is_object($schema)) {
            throw new InvalidSchemaException(
                'JSON Schema must be an object or a boolean',
                $context
            );
        }

        /** @var StaticKeywordInterface[] $prioritizedKeywords */
        $prioritizedKeywords = [];

        foreach ($schema as $keywordName => $keywordValue) {
            $keyword = $context->getDraft()->getKeywordByName($keywordName);
            if ($keyword instanceof StaticKeywordInterface) {
                $prioritizedKeywords[$keyword->getPriority()] = $keyword;
            }
        }

        ksort($prioritizedKeywords);

        foreach ($prioritizedKeywords as $keyword) {
            $context->pushSchema(keywordLocationFragment: $keyword->getName());
            $keyword->evaluateStatic($schema->{$keyword->getName()}, $context);
            $context->popSchema();
        }
    }

    /**
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException
     */
    public function evaluate(RuntimeEvaluationContext $context, bool $mutationsOnly = false): bool
    {
        $schema = $context->getSchema();

        if (is_bool($schema)) {
            return $schema;
        }

        /** @var KeywordInterface[] $prioritizedKeywords */
        $prioritizedKeywords = [];

        foreach ($schema as $keywordName => $keywordValue) {
            $keyword = $context->getDraft()->getKeywordByName($keywordName);

            if ($mutationsOnly && !$keyword instanceof MutationKeywordInterface) {
                continue;
            }

            $prioritizedKeywords[$keyword->getPriority()] = $keyword;
        }

        ksort($prioritizedKeywords);

        $lastResultNumber = $context->getLastResultNumber();
        $shortCircuit = $context->getConfig()->getShortCircuit();
        $valid = true;

        foreach ($prioritizedKeywords as $keyword) {
            $context->pushSchema(keywordLocationFragment: $keyword->getName());

            $evaluationResult = $keyword->evaluate($schema->{$keyword->getName()}, $context);
            if ($evaluationResult && !$evaluationResult->getValid()) {
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

    public function createBigNumber(mixed $value, bool $acceptNumericStrings = false): ?BigNumberInterface
    {
        if ($value instanceof BigNumberInterface) {
            return clone $value;
        }

        if (!is_int($value) && !is_float($value) && (!$acceptNumericStrings || !is_string($value))) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        try {
            return new BigNumber(sprintf('%f', $value));
        } catch (\InvalidArgumentException) {
            // Instance is not a number
        }

        return null;
    }

    public function valueIsNumeric(mixed $value): bool
    {
        return is_int($value) || is_float($value) || $value instanceof BigNumberInterface;
    }

    public function valuesAreEqual(mixed $value1, mixed $value2): bool
    {
        if ($this->valueIsNumeric($value1) && $this->valueIsNumeric($value2)) {
            return $this->createBigNumber($value1)->equals($this->createBigNumber($value2));
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

    protected function decodeJsonPointerToken(string $fragment): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], urldecode($fragment));
    }
}