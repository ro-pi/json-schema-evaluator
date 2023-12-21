<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Exception;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Exception\JsonSchemaEvaluatorException;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class StaticKeywordAnalysisException extends JsonSchemaEvaluatorException
{
    public function __construct(
        string $message,
        private readonly StaticKeywordInterface $keyword,
        private readonly StaticEvaluationContext $context
    ) {
        parent::__construct(sprintf($message, $this->keyword->getName()));
    }

    /**
     * @noinspection PhpUnused
     */
    public function getContext(): StaticEvaluationContext
    {
        return $this->context;
    }

    /**
     * @noinspection PhpUnused
     */
    public function getKeyword(): StaticKeywordInterface
    {
        return $this->keyword;
    }
}