<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationConfig;

class RuntimeEvaluationConfig
{
    public function __construct(
        public /*readonly*/ bool $evaluateMutations = false,
        public /*readonly*/ bool $assertFormat = false,
        public /*readonly*/ bool $assertContentMediaTypeEncoding = false,
        public /*readonly*/ bool $shortCircuit = false
    ) {}
}
