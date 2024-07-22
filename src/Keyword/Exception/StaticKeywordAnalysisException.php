<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Exception;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Exception\JsonSchemaEvaluatorException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class StaticKeywordAnalysisException extends JsonSchemaEvaluatorException
{
    private readonly string $keywordLocation;

    public function __construct(
        string $message,
        private readonly StaticKeywordInterface $keyword,
        private readonly StaticEvaluationContext $context
    ) {
        parent::__construct(sprintf($message, $this->keyword->getName()));
        $this->keywordLocation = $context->getCurrentSchemaKeywordLocation();
    }

    public function getKeywordLocation(): string
    {
        return $this->keywordLocation;
    }

    public function getContext(): StaticEvaluationContext
    {
        return $this->context;
    }

    public function getKeyword(): StaticKeywordInterface
    {
        return $this->keyword;
    }
}