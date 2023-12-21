<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Draft\Exception;

use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;

class InvalidSchemaException extends DraftException
{
    public function __construct(
        string $message,
        private readonly StaticEvaluationContext $context
    ) {
        parent::__construct($message);
    }

    /**
     * @noinspection PhpUnused
     */
    public function getContext(): StaticEvaluationContext
    {
        return $this->context;
    }
}