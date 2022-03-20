<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationResult
{
    public bool $valid = true;
    public ?string $error = null;
    public mixed $errorMeta = null;
    private mixed $annotation = null;
    public ?bool $suppressAnnotation = false;
    private ?bool $evaluationResult = null;

    public function __construct(
        public /*readonly*/ int $number,
        public /*readonly*/ KeywordInterface $keyword,
        public /*readonly*/ string $keywordLocation,
        public /*readonly*/ string $instanceLocation,
        public /*readonly*/ ?string $absoluteKeywordLocation
    ) {}

    public function setEvaluationResult(bool $evaluationResult): void
    {
        $this->evaluationResult = $evaluationResult;
    }

    public function getEvaluationResult(): bool
    {
        if ($this->evaluationResult === null) {
            return $this->valid;
        }

        return $this->evaluationResult;
    }

    public function setAnnotation(mixed $annotation): void
    {
        $this->annotation = $annotation;
    }

    public function getAnnotation(bool $force = false): mixed
    {
        if (!$force && $this->suppressAnnotation) {
            return null;
        }

        return $this->annotation;
    }

    public function hasAnnotation(bool $force = false): bool
    {
        return $this->getAnnotation($force) !== null;
    }

    public function invalidate(string $error, mixed $errorMeta = null): void
    {
        $this->error = $error;
        $this->errorMeta = $errorMeta;
        $this->valid = false;
    }
}
