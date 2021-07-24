<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationResult
{
    private bool $valid = true;
    private ?string $error = null;
    private mixed $errorMeta = null;
    private mixed $annotation = null;
    private ?bool $suppressAnnotation = false;
    private ?bool $evaluationResult = null;

    public function __construct(
        private int $resultNumber,
        private KeywordInterface $keyword,
        private string $keywordLocation,
        private string $instanceLocation,
        private ?string $absoluteKeywordLocation
    ) {}

    public function getNumber(): int
    {
        return $this->resultNumber;
    }

    public function getKeyword(): KeywordInterface
    {
        return $this->keyword;
    }

    public function setEvaluationResult(bool $evaluationResult): void
    {
        $this->evaluationResult = $evaluationResult;
    }

    public function getEvaluationResult(): bool
    {
        if ($this->evaluationResult === null) {
            return $this->getValid();
        }

        return $this->evaluationResult;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getValid(): bool
    {
        return $this->valid;
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

    public function getKeywordLocation(): string
    {
        return $this->keywordLocation;
    }

    public function getAbsoluteKeywordLocation(): ?string
    {
        return $this->absoluteKeywordLocation;
    }

    public function getInstanceLocation(): string
    {
        return $this->instanceLocation;
    }
}
