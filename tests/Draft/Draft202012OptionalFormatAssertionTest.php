<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\AbstractJsonSchemaTestSuite;

class Draft202012OptionalFormatAssertionTest extends AbstractJsonSchemaTestSuite
{
    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12/optional/format-assertion.json';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(object|bool $testCollection)
    {
        $draftFormatAssertionFalse = new Draft202012('http://localhost:1234/draft2020-12/format-assertion-false.json');
        $draftFormatAssertionFalse->enableVocabulary('https://json-schema.org/draft/2020-12/vocab/format-assertion');

        $draftFormatAssertionTrue = new Draft202012('http://localhost:1234/draft2020-12/format-assertion-true.json');
        $draftFormatAssertionTrue->enableVocabulary('https://json-schema.org/draft/2020-12/vocab/format-assertion');

        $this->evaluateTestCollection(
            $testCollection,
            new StaticEvaluationConfig(
                defaultDraft: new Draft202012(),
                supportedDrafts: [
                    $draftFormatAssertionFalse,
                    $draftFormatAssertionTrue
                ]
            )
        );
    }
}
