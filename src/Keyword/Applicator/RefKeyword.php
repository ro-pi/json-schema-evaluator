<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Keyword\Applicator;

use Psr\Http\Message\UriInterface;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\AbstractKeyword;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\InvalidKeywordValueException;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\RuntimeKeywordInterface;
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class RefKeyword extends AbstractKeyword implements StaticKeywordInterface, RuntimeKeywordInterface
{
    /**
     * @var array<string, bool>
     */
    private array $processedSchemaInstanceLocations = [];

    public function getName(): string
    {
        return '$ref';
    }

    /**
     * @throws StaticKeywordAnalysisException
     * @throws \Ropi\JsonSchemaEvaluator\Draft\Exception\InvalidSchemaException
     * @throws \Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaParseException
     * @throws \Ropi\JsonSchemaEvaluator\SchemaPool\Exception\RemoteSchemaRequestException
     */
    public function evaluateStatic(mixed &$keywordValue, StaticEvaluationContext $context): void
    {
        if (!is_string($keywordValue)) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a string.',
                $this,
                $context
            );
        }

        $uri = $context->draft->tryCreateUri($keywordValue);
        if (!$uri) {
            throw new InvalidKeywordValueException(
                'The value of \'%s\' must be a valid URI reference.',
                $this,
                $context
            );
        }

        $resolvedUri = $context->draft->resolveUri($context->getCurrentBaseUri(), $uri);
        $normalizedUri = $resolvedUri->withFragment('');

        $config = $context->config;

        if (
            !$context->hasSchema((string)$normalizedUri)
            && !$this->scanSchemaForId($context->getRootSchema(), $normalizedUri, $context))
        {
            $remoteSchema = $config->schemaPool->getSchemaByUri((string)$normalizedUri);
            if (!$remoteSchema) {
                $remoteSchema = $config->schemaPool->fetchRemoteSchema((string)$normalizedUri);
            }

            if (!isset($remoteSchema->{'$id'})) {
                $context->registerSchema(
                    (string)$normalizedUri,
                    $remoteSchema,
                    $context->getCurrentSchemaKeywordLocation(-1)
                );
            }

            $context->pushSchema(schema: $remoteSchema, baseUri: (string)$normalizedUri);
            $context->draft->evaluateStatic($context);
            $context->popSchema();
        }

        $keywordValue = (string)$resolvedUri;
    }

    /**
     * @throws KeywordRuntimeEvaluationException
     */
    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        /** @var string $keywordValue */

        $uriComponents = explode('#', $keywordValue, 2);
        $fragment = $uriComponents[1] ?? '';

        if ($fragment && str_starts_with($fragment, '/')) {
            $referencedSchema = $this->dereferenceSchema($uriComponents[0], $context);
            $referencedSchema = $context->draft->dereferenceJsonPointer($referencedSchema, $fragment);
            if (!is_bool($referencedSchema) && !$referencedSchema instanceof \stdClass) {
                throw new KeywordRuntimeEvaluationException(
                    'Can not dereference valid schema from JSON pointer \'' . $fragment . '\'.',
                    $this,
                    $context
                );
            }
        } else {
            $referencedSchema = $this->dereferenceSchema($keywordValue, $context);
        }

        /** @var \stdClass $currentSchema */
        $currentSchema = $context->getCurrentSchema();

        $schemaInstanceLocationHash = spl_object_hash($currentSchema) . ':' . $context->getCurrentInstanceLocation();
        if (isset($this->processedSchemaInstanceLocations[$schemaInstanceLocationHash])) {
            throw new KeywordRuntimeEvaluationException(
                '\'%s\' causes an infinite recursion.',
                $this,
                $context
            );
        }

        $this->processedSchemaInstanceLocations[$schemaInstanceLocationHash] = true;

        $result = $context->createResultForKeyword($this);

        $context->pushSchema(
            schema: $referencedSchema,
            baseUri: $uriComponents[0],
            schemaLocation: (string)$context->staticEvaluationContext->getSchemaLocationByUri($keywordValue)
        );

        $result->valid = $context->draft->evaluate($context);

        $context->popSchema();

        unset($this->processedSchemaInstanceLocations[$schemaInstanceLocationHash]);

        return $result;
    }

    /**
     * @throws KeywordRuntimeEvaluationException
     */
    private function dereferenceSchema(string $schemaUri, RuntimeEvaluationContext $context): \stdClass
    {
        $referencedSchema = $context->staticEvaluationContext->getSchemaByUri($schemaUri);
        if (!$referencedSchema) {
            throw new KeywordRuntimeEvaluationException(
                'Failed to dereference schema URI \'' . $schemaUri . '\'.',
                $this,
                $context
            );
        }

        return $referencedSchema;
    }

    private function scanSchemaForId(mixed $schemaPart, UriInterface $uri, StaticEvaluationContext $context): bool
    {
        if ($schemaPart instanceof \stdClass) {
            if (isset($schemaPart->{'$id'}) && is_string($schemaPart->{'$id'})) {
                $idUri = $context->draft->tryCreateUri($schemaPart->{'$id'});
                if ($idUri) {
                    $idUri = $context->draft->resolveUri($context->getCurrentBaseUri(), $idUri);
                    if ((string)$idUri == (string)$uri) {
                        return true;
                    }
                }
            }

            foreach (get_object_vars($schemaPart) as $propertyName => $propertyValue) {
                if ($propertyName === '$id') {
                    continue;
                }

                if ($this->scanSchemaForId($propertyValue, $uri, $context)) {
                    return true;
                }
            }

            return false;
        }

        if (is_array($schemaPart)) {
            foreach ($schemaPart as $item) {
                if ($this->scanSchemaForId($item, $uri, $context)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

}