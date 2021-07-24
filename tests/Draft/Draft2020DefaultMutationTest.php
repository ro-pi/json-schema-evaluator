<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\RuntimeEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\AbstractJsonSchemaTestSuite;

class Draft2020DefaultMutationTest extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012();
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12/default.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(object|bool $testCollection)
    {
        $staticEvaluationConfig = new StaticEvaluationConfig($this->draft);
        $runtimeEvaluationConfig = new RuntimeEvaluationConfig(
            evaluateMutations: true // Enabled mutations should not affect validation result of default keyword
        );

        $this->evaluateTestCollection(
            $testCollection,
            $staticEvaluationConfig,
            $runtimeEvaluationConfig
        );
    }
}
