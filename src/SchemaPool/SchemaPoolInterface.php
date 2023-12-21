<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\SchemaPool;

use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException;
use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException;

interface SchemaPoolInterface
{
    function registerSchema(string $uri, \stdClass $schema): void;

    /**
     * @throws RemoteSchemaParseException
     * @throws RemoteSchemaRequestException
     */
    function fetchRemoteSchema(string $uri): \stdClass;

    function getSchemaByUri(string $uri): ?\stdClass;

    /**
     * @return array<string, \stdClass>
     */
    function getSchemas(): array;
}