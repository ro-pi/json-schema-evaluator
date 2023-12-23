<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Output;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\Output\FlagOutput;

class FlagOutputTest extends TestCase
{
    public function testValid(): void
    {
        $flagOutput = new FlagOutput(true);

        $this->assertTrue($flagOutput->getValid());

        $this->assertJsonStringEqualsJsonString(
            '{"valid":true}',
            (string)json_encode($flagOutput->format())
        );
    }

    public function testInvalid(): void
    {
        $flagOutput = new FlagOutput(false);

        $this->assertFalse($flagOutput->getValid());

        $this->assertJsonStringEqualsJsonString(
            '{"valid":false}',
            (string)json_encode($flagOutput->format())
        );
    }
}
