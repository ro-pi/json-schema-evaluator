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
use Ropi\JsonSchemaEvaluator\Keyword\StaticKeywordInterface;

class RefKeyword extends AbstractKeyword implements StaticKeywordInterface
{
    protected array $processedSchemaInstanceLocations = [];

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
                'The value of "%s" must be a string',
                $this,
                $context
            );
        }

        $uri = $context->getDraft()->createUri($keywordValue);
        if (!$uri) {
            throw new InvalidKeywordValueException(
                'The value of "%s" must be a valid URI reference',
                $this,
                $context
            );
        }

        $resolvedUri = $context->getDraft()->resolveUri($context->getBaseUri(), $uri);
        $normalizedUri = $resolvedUri->withFragment('');

        $config = $context->getConfig();
        $schemaPool = $config->getSchemaPool();

        if (
            !$context->hasSchema((string) $normalizedUri)
            && !$this->scanSchemaForId($context->getRootSchema(), $normalizedUri, $context))
        {
            $remoteSchema = $schemaPool->getSchemaByUri((string) $normalizedUri);
            if (!$remoteSchema) {
                $remoteSchema = $schemaPool->fetchRemoteSchema((string) $normalizedUri);
            }

            if (!isset($remoteSchema->{'$id'})) {
                $context->registerSchema(
                    (string) $normalizedUri,
                    $remoteSchema,
                    $context->getSchemaKeywordLocation(-1)
                );
            }

            $context->pushSchema(schema: $remoteSchema, baseUri: (string) $normalizedUri);
            $context->getDraft()->evaluateStatic($context);
            $context->popSchema();
        }

        $keywordValue = (string) $resolvedUri;
    }

    /**
     * @throws KeywordRuntimeEvaluationException
     */
    public function evaluate(mixed $keywordValue, RuntimeEvaluationContext $context): ?RuntimeEvaluationResult
    {
        $uriComponents = explode('#', $keywordValue, 2);
        $fragment = $uriComponents[1] ?? '';

        if ($fragment && str_starts_with($fragment, '/')) {
            $referencedSchema = $this->dereferenceSchema($uriComponents[0], $context);
            $referencedSchema = $context->getDraft()->dereferenceJsonPointer($referencedSchema, $fragment);
            if ($referencedSchema === null) {
                throw new KeywordRuntimeEvaluationException(
                    'Can not dereference JSON pointer "' . $fragment . '"',
                    $this,
                    $context
                );
            }
        } else {
            $referencedSchema = $this->dereferenceSchema($keywordValue, $context);
        }

        $schemaInstanceLocationHash = spl_object_hash($context->getSchema()) . ':' . $context->getInstanceLocation();
        if (isset($this->processedSchemaInstanceLocations[$schemaInstanceLocationHash])) {
            throw new KeywordRuntimeEvaluationException(
                '"%s" causes an infinite recursion',
                $this,
                $context
            );
        }

        $this->processedSchemaInstanceLocations[$schemaInstanceLocationHash] = true;

        $result = $context->createResultForKeyword($this);

        $context->pushSchema(
            schema: $referencedSchema,
            baseUri: $uriComponents[0],
            schemaLocation: (string) $context->getStaticEvaluationContext()->getSchemaLocationByUri($keywordValue)
        );

        $result->setValid(
            $context->getDraft()->evaluate($context)
        );

        $context->popSchema();

        unset($this->processedSchemaInstanceLocations[$schemaInstanceLocationHash]);

        return $result;
    }

    /**
     * @throws KeywordRuntimeEvaluationException
     */
    protected function dereferenceSchema(string $schemaUri, RuntimeEvaluationContext $context): object
    {
        $referencedSchema = $context->getStaticEvaluationContext()->getSchemaByUri($schemaUri);
        if (!$referencedSchema) {
            throw new KeywordRuntimeEvaluationException(
                'Failed to dereference schema URI "' . $schemaUri . '"',
                $this,
                $context
            );
        }

        return $referencedSchema;
    }

    protected function scanSchemaForId(mixed $schemaPart, UriInterface $uri, StaticEvaluationContext $context): bool
    {
        if (is_object($schemaPart)) {
            if (isset($schemaPart->{'$id'}) && is_string($schemaPart->{'$id'})) {
                $idUri = $context->getDraft()->createUri($schemaPart->{'$id'});
                if ($idUri) {
                    $idUri = $context->getDraft()->resolveUri($context->getBaseUri(), $idUri);
                    if ((string) $idUri == (string) $uri) {
                        return true;
                    }
                }
            }

            foreach ($schemaPart as $propertyName => $propertyValue) {
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