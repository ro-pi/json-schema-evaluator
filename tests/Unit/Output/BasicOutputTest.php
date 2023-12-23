<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Output;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\Keyword\UnknownKeyword;
use Ropi\JsonSchemaEvaluator\Output\BasicOutput;

class BasicOutputTest extends TestCase
{
    public function testValid(): void
    {
        $results = [new RuntimeEvaluationResult(123, new UnknownKeyword(1, 'test'), 'keyword', 'instance', 'absolute')];
        $basicOutput = new BasicOutput(true, $results);

        $this->assertTrue($basicOutput->getValid());
        $this->assertEquals($results, $basicOutput->getResults());

        $this->assertJsonStringEqualsJsonString(
            '{"valid":true}',
            (string)json_encode($basicOutput->format())
        );
    }

    public function testInvalid(): void
    {
        $result = new RuntimeEvaluationResult(123, new UnknownKeyword(1, 'test'), 'keyword', 'instance', 'absolute');
        $result->invalidate('testError', 'testErrorMeta');

        $results = [$result];
        $basicOutput = new BasicOutput(false, $results);

        $this->assertFalse($basicOutput->getValid());
        $this->assertEquals($results, $basicOutput->getResults());

        $this->assertJsonStringEqualsJsonString(
            '{
                "valid":false,
                "errors":[
                    {
                    "valid":false,
                    "keywordLocation":"keyword",
                    "absoluteKeywordLocation":"absolute",
                    "instanceLocation":"instance",
                    "keywordName":"test",
                    "error":"testError",
                    "errorMeta":"testErrorMeta"
                    }
                ]
            }',
            (string)json_encode($basicOutput->format())
        );
    }
}
