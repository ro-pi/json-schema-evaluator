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
        protected array $results
    ) {
        parent::__construct($valid);
    }

    public function format(): object
    {
        $formattedResult = new \stdClass();
        $formattedResult->valid = $this->getValid();

        foreach ($this->results as $result) {
            if (!$result->valid) {
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

    protected function createOutputUnit(RuntimeEvaluationResult $result): object
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