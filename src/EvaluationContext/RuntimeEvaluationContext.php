<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationContext
{
    use EvaluationContextTrait;

    /**
     * @var list<array{
     *     instance: mixed,
     *     instanceLocation: string
     * }>
     */
    private array $instanceStack = [];
    private int $instanceStackPointer = 0;

    /**
     * @var RuntimeEvaluationResult[]
     */
    private array $results = [];
    private int $lastResultNumber = 0;

    public function __construct(
        \stdClass|bool $schema,
        mixed &$instance,
        public readonly StaticEvaluationContext $staticEvaluationContext
    ) {
        $this->schemaStack[0] = [
            'schema' => $schema,
            'keywordLocation' => '',
            'schemaKeywordLocation' => '',
            'baseUri' => '',
        ];

        $this->instanceStack[0] = [
            'instance' => &$instance,
            'instanceLocation' => ''
        ];

        $this->draft = $staticEvaluationContext->config->defaultDraft;
    }

    public function pushInstance(mixed &$instance, string $instanceLocationFragment = null): void
    {
        if ($instanceLocationFragment === null) {
            $instanceLocation = $this->getCurrentInstanceLocation();
        } else {
            $instanceLocation = $this->getCurrentInstanceLocation() . '/' . $instanceLocationFragment;
        }

        $this->instanceStack[++$this->instanceStackPointer] = [
            'instance' => &$instance,
            'instanceLocation' => $instanceLocation
        ];
    }

    public function popInstance(): void
    {
        if ($this->instanceStackPointer <= 0) {
            throw new \RuntimeException(
                'Can not pop root instance',
                1623271888
            );
        }

        $this->instanceStackPointer--;
    }

    public function getCurrentInstanceLocation(): string
    {
        return $this->instanceStack[$this->instanceStackPointer]['instanceLocation'];
    }

    public function &getCurrentInstance(): mixed
    {
        return $this->instanceStack[$this->instanceStackPointer]['instance'];
    }

    public function createResultForKeyword(KeywordInterface $keyword): RuntimeEvaluationResult
    {
        $result = new RuntimeEvaluationResult(
            ++$this->lastResultNumber,
            $keyword,
            $this->schemaStack[$this->schemaStackPointer]['keywordLocation'],
            $this->instanceStack[$this->instanceStackPointer]['instanceLocation'],
            $this->getCurrentAbsoluteKeywordLocation()
        );

        $this->results[] = $result;

        return $result;
    }

    public function getLastResultNumber(): int
    {
        return $this->lastResultNumber;
    }

    /**
     * @return RuntimeEvaluationResult[]
     */
    public function getResultsByKeywordName(string $keywordName): array
    {
        $results = [];
        $currentInstanceLocation = $this->getCurrentInstanceLocation();

        foreach ($this->results as $result) {
            if (
                $result->instanceLocation === $currentInstanceLocation
                && $result->keyword->getName() === $keywordName
            ) {
                $results[] = $result;
            }
        }

        return $results;
    }

    public function getLastResultByKeywordName(string $keywordName): ?RuntimeEvaluationResult
    {
        $results = $this->getResultsByKeywordName($keywordName);
        return end($results) ?: null;
    }

    public function getLastResultByKeywordLocation(string $keywordLocation): ?RuntimeEvaluationResult
    {
        $lastDelimiterPosition = strrpos($keywordLocation, '/');
        if ($lastDelimiterPosition === false) {
            return null;
        }

        $keywordName = substr($keywordLocation, $lastDelimiterPosition + 1);
        $results = $this->getResultsByKeywordName($keywordName);

        foreach (array_reverse($results) as $result) {
            if ($result->keywordLocation === $keywordLocation) {
                return $result;
            }
        }

        return null;
    }

    public function getLastResult(): ?RuntimeEvaluationResult
    {
        return end($this->results) ?: null;
    }

    /**
     * @return RuntimeEvaluationResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function adoptResultsFromContext(RuntimeEvaluationContext $context): void
    {
        foreach ($context->getResults() as $result) {
            $this->results[] = $result;
        }
    }

    public function suppressAnnotations(?int $after = null): void
    {
        foreach ($this->results as $result) {
            if ($result->number > $after) {
                $result->suppressAnnotation = true;
            }
        }
    }

    public function getMostOuterDynamicAnchorUri(string $dynamicAnchor): ?string
    {
        foreach ($this->schemaStack as $stackEntry) {
            $dynamicAnchorUri = $stackEntry['baseUri'] . '#' . $dynamicAnchor;
            if ($this->staticEvaluationContext->hasDynamicAnchorUri($dynamicAnchorUri)) {
                return $dynamicAnchorUri;
            }
        }

        return null;
    }

    public function __clone(): void
    {
        $this->results = [];
    }
}