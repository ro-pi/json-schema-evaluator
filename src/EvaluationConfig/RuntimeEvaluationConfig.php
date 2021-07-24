<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationConfig;

class RuntimeEvaluationConfig
{
    public function __construct(
        private bool $evaluateMutations = false,
        private bool $assertFormat = false,
        private bool $assertContentMediaTypeEncoding = false,
        private bool $shortCircuit = false
    ) {}

    public function getEvaluateMutations(): bool
    {
        return $this->evaluateMutations;
    }

    public function getAssertFormat(): bool
    {
        return $this->assertFormat;
    }

    public function getAssertContentMediaTypeEncoding(): bool
    {
        return $this->assertContentMediaTypeEncoding;
    }

    public function getShortCircuit(): bool
    {
        return $this->shortCircuit;
    }
}
