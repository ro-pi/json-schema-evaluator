<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext\Struct;

class InstanceStackEntry
{
    public function __construct(
        public mixed &$instance,
        public string $instanceLocation
    ){}
}