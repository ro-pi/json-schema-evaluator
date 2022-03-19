<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword;

interface KeywordInterface
{
    function getName(): string;
    function getPriority(): ?int;
}