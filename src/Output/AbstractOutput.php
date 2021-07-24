<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Output;

abstract class AbstractOutput implements OutputInterface
{
    public function __construct(
        private bool $valid
    ){}

    public function getValid(): bool
    {
        return $this->valid;
    }
}