<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Compliance\Draft2020;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\Compliance\AbstractJsonSchemaTestSuite;

class Draft2020DefaultMutationTest extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012(
            evaluateMutations: true // Enabled mutations should not affect validation result of default keyword
        );
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12/default.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(\stdClass $testCollection): void
    {
        $staticEvaluationConfig = new StaticEvaluationConfig($this->draft);

        $this->evaluateTestCollection(
            $testCollection,
            $staticEvaluationConfig
        );
    }
}
