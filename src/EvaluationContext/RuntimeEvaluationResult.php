<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;

class RuntimeEvaluationResult
{
    public const TYPE_ERROR = 'error';
    public const TYPE_ANNOTATION = 'annotation';

    public string $type = self::TYPE_ANNOTATION;
    public bool $valid = true;
    public ?string $error = null;
    public mixed $errorMeta = null;
    private mixed $annotationValue = null;

    public function __construct(
        public readonly int $number,
        public readonly KeywordInterface $keyword,
        public readonly mixed $keywordValue,
        public readonly string $keywordLocation,
        public readonly string $instanceLocation,
        public readonly ?string $absoluteKeywordLocation
    ) {}

    public function setAnnotationValue(mixed $annotationValue): void
    {
        $this->annotationValue = $annotationValue;
    }

    public function getAnnotationValue(): mixed
    {
        return $this->annotationValue;
    }

    public function hasAnnotationValue(): bool
    {
        return $this->getAnnotationValue() !== null;
    }

    public function invalidate(?string $error = null, mixed $errorMeta = null): void
    {
        $this->type = self::TYPE_ERROR;
        $this->error = $error;
        $this->errorMeta = $errorMeta;
        $this->valid = false;
    }
}
