<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;

class StaticEvaluationContext
{
    use EvaluationContextTrait;

    private array $schemas = [];
    private array $dynamicAnchors = [];
    private array $schemaLocations = [];
    private \WeakMap $prioritizedSchemaKeywords;

    public function __construct(
        object|bool $schema,
        public /*readonly*/ StaticEvaluationConfig $config
    ) {
        $this->draft = $this->config->defaultDraft;
        $this->prioritizedSchemaKeywords = new \WeakMap();

        $this->schemaStack[0] = [
            'schema' => $schema,
            'keywordLocation' => '',
            'schemaKeywordLocation' => '',
            'baseUri' => '',
        ];

        if (is_object($schema)) {
            $this->registerSchema('', $schema, '');
        }
    }

    public function registerPrioritizedSchemaKeywords(object $schema, array $prioritizedKeywords): void
    {
        $this->prioritizedSchemaKeywords[$schema] = $prioritizedKeywords;
    }

    public function hasPrioritizedSchemaKeywords(object $schema): bool
    {
        return isset($this->prioritizedSchemaKeywords[$schema]);
    }

    public function getPrioritizedSchemaKeywords(object $schema): array
    {
        return $this->prioritizedSchemaKeywords[$schema] ?? [];
    }

    public function registerSchema(string $uri, object $schema, string $location): void
    {
        $this->schemas[$uri] = $schema;
        $this->schemaLocations[$uri] = $location;
    }

    public function hasSchema(string $uri): bool
    {
        return isset($this->schemas[$uri]);
    }

    public function getSchemaByUri(string $uri): ?object
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