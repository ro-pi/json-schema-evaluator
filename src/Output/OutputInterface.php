<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Output;

interface OutputInterface
{
    function getValid(): bool;
    function format(): \stdClass;
}