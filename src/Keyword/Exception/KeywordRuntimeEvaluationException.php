<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Exception;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\KeywordInterface;
use Ropi\JsonSchemaEvaluator\Exception\JsonSchemaEvaluatorException;

class KeywordRuntimeEvaluationException extends JsonSchemaEvaluatorException
{
    public function __construct(
        string $message,
        private readonly KeywordInterface $keyword,
        private readonly RuntimeEvaluationContext $context
    ) {
        parent::__construct(sprintf($message, $this->keyword->getName()));
    }

    /**
     * @noinspection PhpUnused
     */
    public function getContext(): RuntimeEvaluationContext
    {
        return $this->context;
    }

    /**
     * @noinspection PhpUnused
     */
    public function getKeyword(): KeywordInterface
    {
        return $this->keyword;
    }
}