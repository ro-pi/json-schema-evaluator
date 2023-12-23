<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Compliance;

use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException;
use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPool;

class JsonSchemaTestSuiteSchemaPool extends SchemaPool
{
    /**
     * @throws RemoteSchemaParseException
     * @throws RemoteSchemaRequestException
     */
    public function fetchRemoteSchema(string $uri): \stdClass
    {
        if (str_starts_with($uri, 'http://localhost:1234')) {
            $relativeFilePath = substr($uri, strlen('http://localhost:1234'));
            $absoluteFilePath = dirname(__DIR__) . '/Resources/json-schema-test-suite/remotes' . $relativeFilePath;

            $content = file_get_contents($absoluteFilePath);
            if (!is_string($content)) {
                throw new RemoteSchemaRequestException(
                    'Failed to open json schema test suite remote schema with URI \''
                    . $uri
                    . '\'',
                    1703195612
                );
            }

            $schema = json_decode($content);
            if (!$schema instanceof \stdClass) {
                throw new RemoteSchemaParseException(
                    'The json schema test suite remote schema URI \''
                    . $uri
                    . '\' does not contain a valid JSON Schema object',
                    1703195642
                );
            }

            return $schema;
        }

        return parent::fetchRemoteSchema($uri);
    }
}