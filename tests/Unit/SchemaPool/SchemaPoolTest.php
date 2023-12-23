<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\SchemaPool;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPool;

class SchemaPoolTest extends TestCase
{
    /**
     * @throws \Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException
     */
    public function testValid(): void
    {
        $this->expectException(RemoteSchemaRequestException::class);
        $this->expectWarning();

        $pool = new SchemaPool();
        $pool->fetchRemoteSchema('http://localhost:1234/not-existing-uri');
    }
}
