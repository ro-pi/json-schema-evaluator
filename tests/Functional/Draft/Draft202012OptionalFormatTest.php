<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Functional\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\Functional\AbstractJsonSchemaTestSuite;

class Draft202012OptionalFormatTest extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    /**
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012(
            assertFormat: true,
            evaluateMutations: false
        );
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12/optional/format';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
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