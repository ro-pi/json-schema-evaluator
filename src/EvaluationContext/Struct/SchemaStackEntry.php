<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext\Struct;

class SchemaStackEntry
{
    public function __construct(
        public object|bool $schema,
        public string $keywordLocation,
        public string $schemaKeywordLocation,
        public string $baseUri
    ){}
}