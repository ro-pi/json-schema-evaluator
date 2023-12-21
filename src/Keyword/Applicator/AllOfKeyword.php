<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class AllOfKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    use OfKeywordTrait;

    public function getName(): string
    {
        return 'allOf';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $this->evaluateStaticOf($keywordValue, $this, $context);
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var list<\stdClass|bool> $keywordValue */

        $result = $context->createResultForKeyword($this);
        $numMatches = $this->evaluateOf($keywordValue, $context);

        if ($numMatches !== count($keywordValue)) {
            $result->invalidate(
                'Value must match all schemas, but matches only '
                . $numMatches,
                $numMatches
            );
        }

        return $result;
    }
}