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

class OneOfKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    use OfKeywordTrait;

    public function getName(): string
    {
        return 'oneOf';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        $this->evaluateStaticOf($keywordValue, $this, $context);
    }

    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var list<\stdClass|bool> $keywordValue */

        $result = $context->createResultForKeyword($this, $keywordValue);
        $numMatches = $this->evaluateOf($keywordValue, $context);

        if ($numMatches !== 1) {
            $result->invalidate(
                'Value must match exactly one schema, but matches '
                . $numMatches,
                $numMatches
            );
        }

        return $result;
    }
}