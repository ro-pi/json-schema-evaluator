<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Functional\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\Functional\AbstractJsonSchemaTestSuite;

class Draft202012OptionalBigNumTest extends AbstractJsonSchemaTestSuite
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
        return 'draft2020-12/optional/bignum.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(\stdClass $testCollection): void
    {
        $this->evaluateTestCollection($testCollection, new StaticEvaluationConfig(
            defaultDraft: $this->draft
        ));
    }
}
