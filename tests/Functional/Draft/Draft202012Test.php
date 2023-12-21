<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Functional\Draft;

use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\Tests\Functional\AbstractJsonSchemaTestSuite;
use Ropi\JsonSchemaEvaluator\Tests\Functional\JsonSchemaTestSuiteSchemaPool;

class Draft202012Test extends AbstractJsonSchemaTestSuite
{
    private Draft202012 $draft;

    public function setUp(): void
    {
        parent::setUp();
        $this->draft = new Draft202012();
    }

    protected function getRelativeTestsPath(): string
    {
        return 'draft2020-12';
    }

    /**
     * @dataProvider jsonSchemaTestSuiteProvider
     *
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    public function test(\stdClass $testCollection): void
    {
        $metaSchemaNoValidation = new Draft202012('http://localhost:1234/draft2020-12/metaschema-no-validation.json');
        foreach ($metaSchemaNoValidation->getSupportedVocabularies() as $vocabulary) {
            $metaSchemaNoValidation->disableVocabulary($vocabulary);
        }

        $metaSchemaNoValidation->enableVocabulary(Draft202012::VOCABULARY_APPLICATOR);
        $metaSchemaNoValidation->enableVocabulary(Draft202012::VOCABULARY_CORE);

        $this->evaluateTestCollection($testCollection, new StaticEvaluationConfig(
            defaultDraft: $this->draft,
            supportedDrafts: [
                $metaSchemaNoValidation
            ],
            schemaPool: new JsonSchemaTestSuiteSchemaPool()
        ));
    }
}
