<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class StaticEvaluationContext
{
    use EvaluationContextTrait;

    /**
     * @var array<string, \stdClass>
     */
    private array $schemas = [];

    /**
     * @var array<string, bool>
     */
    private array $dynamicAnchors = [];

    /**
     * @var array<string, string>
     */
    private array $schemaLocations = [];

    /**
     * @var \WeakMap<\stdClass, KeywordInterface[]>
     */
    private \WeakMap $prioritizedSchemaKeywords;

    public function __construct(
        \stdClass|bool $schema,
        public readonly StaticEvaluationConfig $config
    ) {
        $this->draft = $this->config->defaultDraft;
        $this->prioritizedSchemaKeywords = new \WeakMap();

        $this->schemaStack[0] = [
            'schema' => $schema,
            'keywordLocation' => '',
            'schemaKeywordLocation' => '',
            'baseUri' => '',
        ];

        if ($schema instanceof \stdClass) {
            $this->registerSchema('', $schema, '');
        }
    }

    /**
     * @param KeywordInterface[] $prioritizedKeywords
     */
    public function registerPrioritizedSchemaKeywords(\stdClass $schema, array $prioritizedKeywords): void
    {
        $this->prioritizedSchemaKeywords[$schema] = $prioritizedKeywords;
    }

    public function hasPrioritizedSchemaKeywords(\stdClass $schema): bool
    {
        return isset($this->prioritizedSchemaKeywords[$schema]);
    }

    /**
     * @return KeywordInterface[]
     */
    public function getPrioritizedSchemaKeywords(\stdClass $schema): array
    {
        return $this->prioritizedSchemaKeywords[$schema] ?? [];
    }

    public function registerSchema(string $uri, \stdClass $schema, string $location): void
    {
        $this->schemas[$uri] = $schema;
        $this->schemaLocations[$uri] = $location;
    }

    public function hasSchema(string $uri): bool
    {
        return isset($this->schemas[$uri]);
    }

    public function getSchemaByUri(string $uri): ?\stdClass
    {
        return $this->schemas[$uri] ?? null;
    }

    public function getSchemaLocationByUri(string $uri): ?string
    {
        return $this->schemaLocations[$uri] ?? null;
    }

    public function registerDynamicAnchor(string $dynamicAnchorUri): void
    {
        $this->dynamicAnchors[$dynamicAnchorUri] = true;
    }

    public function hasDynamicAnchorUri(string $dynamicAnchorUri): bool
    {
        return isset($this->dynamicAnchors[$dynamicAnchorUri]);
    }
}