<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\Struct\SchemaStackEntry;

class StaticEvaluationContext
{
    use EvaluationContextTrait;

    private array $schemas = [];
    private array $dynamicAnchors = [];
    private array $schemaLocations = [];

    public function __construct(
        object|bool $schema,
        private StaticEvaluationConfig $config
    ) {
        $this->draft = $this->config->getDefaultDraft();
        $this->schemaStack[0] = new SchemaStackEntry($schema, '', '', '');

        if (is_object($schema)) {
            $this->registerSchema('', $schema, '');
        }
    }

    public function getConfig(): StaticEvaluationConfig
    {
        return $this->config;
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