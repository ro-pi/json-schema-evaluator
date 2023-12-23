<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Compliance\Draft2020;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\Compliance\AbstractJsonSchemaTestSuite;

class Draft202012OptionalNonBmpRegexTest extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012(
            acceptNumericStrings: true
        );
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12/optional/non-bmp-regex.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(\stdClass $testCollection): void
    {
        $this->evaluateTestCollection($testCollection, new StaticEvaluationConfig(
            defaultDraft: $this->draft
        ));
    }
}
