<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Functional\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Tests\Functional\AbstractJsonSchemaTestSuite;

class Draft202012AdditionalRefRecursionTest extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012();
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12-additional/ref-recursion.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     */
    public function test(\stdClass $testCollection): void
    {
        $this->expectException(KeywordRuntimeEvaluationException::class);

        $staticEvaluationConfig = new StaticEvaluationConfig($this->draft);

        $this->evaluateTestCollection(
            $testCollection,
            $staticEvaluationConfig
        );
    }
}
