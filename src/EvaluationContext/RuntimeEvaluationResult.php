<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationResult
{
    public bool $valid = true;
    private ?string $error = null;
    private mixed $errorMeta = null;
    private mixed $annotation = null;
    private ?bool $suppressAnnotation = false;
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
        $this->valid = true;
    }

    public function getAnnotation(bool $force = false): mixed
    {
        if (!$force && (!$this->valid || $this->suppressAnnotation)) {
            return null;
        }

        return $this->annotation;
    }

    public function hasAnnotation(bool $force = false): bool
    {
        return $this->getAnnotation($force) !== null;
    }

    public function suppressAnnotation(): void
    {
        $this->suppressAnnotation = true;
    }

    public function setError(string $error, mixed $errorMeta = null): void
    {
        $this->error = $error;
        $this->errorMeta = $errorMeta;
        $this->valid = false;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getErrorMeta(): mixed
    {
        return $this->errorMeta;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
