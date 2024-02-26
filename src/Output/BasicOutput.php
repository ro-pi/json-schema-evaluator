<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Output;

use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;

class BasicOutput extends AbstractOutput
{
    /**
     * @param bool $valid
     * @param RuntimeEvaluationResult[] $results
     */
    public function __construct(
        bool $valid,
        private readonly array $results
    ) {
        parent::__construct($valid);
    }

    /**
     * @return RuntimeEvaluationResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function format(): \stdClass
    {
        $formattedResult = new \stdClass();
        $formattedResult->valid = $this->getValid();

        foreach ($this->results as $result) {
            $outputUnit = $this->createOutputUnit($result);

            $outputUnit->error = (string)$result->error;
            $outputUnit->errorMeta = $result->errorMeta;

            if ($result->type === RuntimeEvaluationResult::TYPE_ANNOTATION) {
                $outputUnit->annotation = $result->hasAnnotationValue() ? $result->getAnnotationValue() : $result->keywordValue;
            }

            if ($this->getValid()) {
                $formattedResult->annotations[] = $outputUnit;
            } else {
                $formattedResult->errors[] = $outputUnit;
            }
        }

        return $formattedResult;
    }

    private function createOutputUnit(RuntimeEvaluationResult $result): \stdClass
    {
        $outputUnit = new \stdClass();
        $outputUnit->type = $result->type;
        $outputUnit->valid = $result->valid;
        $outputUnit->keywordLocation = $result->keywordLocation;

        $absoluteKeywordLocation = $result->absoluteKeywordLocation;
        if ($absoluteKeywordLocation !== null) {
            $outputUnit->absoluteKeywordLocation = $absoluteKeywordLocation;
        }

        $outputUnit->instanceLocation = $result->instanceLocation;
        $outputUnit->keywordName = $result->keyword->getName();

        return $outputUnit;
    }
}