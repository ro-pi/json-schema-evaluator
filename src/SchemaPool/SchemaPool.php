<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\SchemaPool;

use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException;
use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException;

class SchemaPool implements SchemaPoolInterface
{
    private array $schemas = [];

    public function registerSchema(string $uri, object $schema): void
    {
        $this->schemas[$uri] = $schema;
    }

    public function getSchemaByUri(string $uri): ?object
    {
        return $this->schemas[$uri] ?? null;
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @throws RemoteSchemaParseException
     * @throws RemoteSchemaRequestException
     */
    public function fetchRemoteSchema(string $uri): object
    {
        $streamContext = stream_context_create(
            [
                'http' => [
                    'user_agent' => 'ropi-json-schema-evaluator'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]
        );

        $responseText = file_get_contents($uri, false, $streamContext);
        if (!is_string($responseText)) {
            throw new RemoteSchemaRequestException(
                'Failed to request remote schema with URI "'
                . $uri
                . '"',
                1621967929
            );
        }

        $schema = json_decode($responseText);
        if (!is_object($schema)) {
            throw new RemoteSchemaParseException(
                'The remote schema URI "'
                . $uri
                . '" does not contain a valid JSON Schema object',
                1621967933
            );
        }

        return $schema;
    }
}