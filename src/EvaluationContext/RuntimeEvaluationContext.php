<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\RuntimeEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationContext
{
    use EvaluationContextTrait;

    /**
     * @var array[]
     */
    private array $instanceStack = [];
    private int $instanceStackPointer = 0;

    /**
     * @var RuntimeEvaluationResult[]
     */
    private array $results = [];
    private int $lastResultNumber = 0;

    public function __construct(
        object|bool $schema,
        mixed &$instance,
        public /*readonly*/ RuntimeEvaluationConfig $config,
        public /*readonly*/ StaticEvaluationContext $staticEvaluationContext
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
            $instanceLocation = $this->getInstanceLocation();
        } else {
            $instanceLocation = $this->getInstanceLocation() . '/' . $instanceLocationFragment;
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

        array_pop($this->instanceStack);
        $this->instanceStackPointer--;
    }

    public function getInstanceLocation(): string
    {
        return $this->instanceStack[$this->instanceStackPointer]['instanceLocation'];
    }

    public function &getInstance(): mixed
    {
        return $this->instanceStack[$this->instanceStackPointer]['instance'];
    }

    public function createResultForKeyword(KeywordInterface $keyword): RuntimeEvaluationResult
    {
        $result = new RuntimeEvaluationResult(
            ++$this->lastResultNumber,
            $keyword,
            $this->getKeywordLocation(),
            $this->getInstanceLocation(),
            $this->getAbsoluteKeywordLocation()
        );

        $this->results[] = $result;

        return $result;
    }

    public function getLastResultNumber(): int
    {
        return $this->lastResultNumber;
    }

    public function getResultsByKeywordName(string $keywordName): array
    {
        $results = [];
        $currentInstanceLocation = $this->getInstanceLocation();

        foreach ($this->results as $result) {
            if (
                $result->getInstanceLocation() === $currentInstanceLocation
                && $result->getKeyword()->getName() === $keywordName
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
            if ($result->getKeywordLocation() === $keywordLocation) {
                return $result;
            }
        }

        return null;
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
            if ($result->getNumber() > $after) {
                $result->suppressAnnotation();
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