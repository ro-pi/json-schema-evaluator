<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Draft;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\Draft\Exception\UnsupportedVocabularyException;

class DraftTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->assertEquals(
            'http://localhost/draft/test123',
            (new TestDraft(uri: 'http://localhost/draft/test123'))->getUri()
        );

        $this->assertTrue((new TestDraft(assertFormat: true))->assertFormat());
        $this->assertFalse((new TestDraft(assertFormat: false))->assertFormat());

        $this->assertTrue((new TestDraft(assertContentMediaTypeEncoding: true))->assertContentMediaTypeEncoding());
        $this->assertFalse((new TestDraft(assertContentMediaTypeEncoding: false))->assertContentMediaTypeEncoding());

        $this->assertTrue((new TestDraft(evaluateMutations: true))->evaluateMutations());
        $this->assertFalse((new TestDraft(evaluateMutations: false))->evaluateMutations());

        $this->assertTrue((new TestDraft(acceptNumericStrings: true))->acceptNumericStrings());
        $this->assertFalse((new TestDraft(acceptNumericStrings: false))->acceptNumericStrings());

        $this->assertTrue((new TestDraft(shortCircuit: true))->shortCircuit());
        $this->assertFalse((new TestDraft(shortCircuit: false))->shortCircuit());
    }

    public function testSupportsVocabulary(): void
    {
        $draft = new TestDraft();

        $this->assertTrue($draft->supportsVocabulary(TestDraft::VOCABULARY_1));
        $this->assertTrue($draft->supportsVocabulary(TestDraft::VOCABULARY_2));
        $this->assertFalse($draft->supportsVocabulary('unknown-vocabulary'));
    }

    public function testGetSupportedVocabularies(): void
    {
        $draft = new TestDraft();
        $this->assertEquals([TestDraft::VOCABULARY_1, TestDraft::VOCABULARY_2], $draft->getSupportedVocabularies());
    }

    /**
     * @throws UnsupportedVocabularyException
     */
    public function testDisableEnableVocabulary(): void
    {
        $draft = new TestDraft();

        $draft->disableVocabulary(TestDraft::VOCABULARY_1);
        $this->assertFalse($draft->vocabularyEnabled(TestDraft::VOCABULARY_1));
        $this->assertTrue($draft->vocabularyEnabled(TestDraft::VOCABULARY_2));

        $this->assertEquals(
            [
                TestDraft::VOCABULARY_1 => false,
                TestDraft::VOCABULARY_2 => true,
            ],
            $draft->getVocabularies()
        );

        $draft->enableVocabulary(TestDraft::VOCABULARY_1);
        $this->assertTrue($draft->vocabularyEnabled(TestDraft::VOCABULARY_1));
        $this->assertTrue($draft->vocabularyEnabled(TestDraft::VOCABULARY_2));

        $this->assertEquals(
            [
                TestDraft::VOCABULARY_1 => true,
                TestDraft::VOCABULARY_2 => true,
            ],
            $draft->getVocabularies()
        );
    }

    public function testUnsupportedVocabularyEnabled(): void
    {
        $this->expectException(UnsupportedVocabularyException::class);

        $draft = new TestDraft();
        $draft->vocabularyEnabled('unsupported-vocabulary');
    }

    public function testEnableUnsupportedVocabulary(): void
    {
        $this->expectException(UnsupportedVocabularyException::class);

        $draft = new TestDraft();
        $draft->enableVocabulary('another-unsupported-vocabulary');
    }

    public function testDisableUnsupportedVocabulary(): void
    {
        $this->expectException(UnsupportedVocabularyException::class);

        $draft = new TestDraft();
        $draft->disableVocabulary('another-unsupported-vocabulary');
    }
}
