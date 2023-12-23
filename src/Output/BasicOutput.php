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
            if ($result->error) {
                $outputUnit = $this->createOutputUnit($result);
                $outputUnit->error = $result->error;
                $outputUnit->errorMeta = $result->errorMeta;
                $formattedResult->errors[] = $outputUnit;
            }

            if ($result->hasAnnotation()) {
                $formattedResult->annotations[] = $this->createOutputUnit($result);
            }
        }

        return $formattedResult;
    }

    private function createOutputUnit(RuntimeEvaluationResult $result): \stdClass
    {
        $outputUnit = new \stdClass();
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