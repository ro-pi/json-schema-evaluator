<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\SchemaPool;

use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException;
use Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException;

interface SchemaPoolInterface
{
    function registerSchema(string $uri, object $schema): void;

    /**
     * @throws RemoteSchemaParseException
     * @throws RemoteSchemaRequestException
     */
    function fetchRemoteSchema(string $uri): object;

    function getSchemaByUri(string $uri): ?object;
    function getSchemas(): array;
}