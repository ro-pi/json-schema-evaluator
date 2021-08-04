<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationContext;

use Ropi\JsonSchemaEvaluator\Draft\DraftInterface;

trait EvaluationContextTrait
{
    public DraftInterface $draft;

    /**
     * @var array[]
     */
    private array $schemaStack = [];
    private int $schemaStackPointer = 0;

    public function pushSchema(
        object|bool|null $schema = null,
        string $keywordLocationFragment = null,
        ?string $baseUri = null,
        string $schemaLocation = ''
    ): void {
        $currentStackEntry = $this->schemaStack[$this->schemaStackPointer];
        $schema = $schema ?? $currentStackEntry['schema'];

        if ($baseUri === null) {
            $schemaKeywordLocation = $currentStackEntry['schemaKeywordLocation'];
            $baseUri = $currentStackEntry['baseUri'];
        } else {
            $schemaKeywordLocation = $schemaLocation;
        }

        if ($keywordLocationFragment) {
            $schemaKeywordLocation .= '/' . $keywordLocationFragment;
            $keywordLocation = $currentStackEntry['keywordLocation'] . '/' . $keywordLocationFragment;
        } else {
            $keywordLocation = $currentStackEntry['keywordLocation'];
        }

        $this->schemaStack[++$this->schemaStackPointer] = [
            'schema' => $schema,
            'keywordLocation' => $keywordLocation,
            'schemaKeywordLocation' => $schemaKeywordLocation,
            'baseUri' => $baseUri
        ];
    }

    public function popSchema(): void
    {
        if ($this->schemaStackPointer <= 0) {
            throw new \RuntimeException(
                'Can not pop root schema',
                1623271880
            );
        }

        $this->schemaStackPointer--;
    }

    public function setCurrentSchema(object|bool $schema): void
    {
        if (!$this->schemaStackPointer) {
            throw new \RuntimeException(
                'Setting the root schema is not allowed',
                1626262970
            );
        }

        $this->schemaStack[$this->schemaStackPointer]['schema'] = $schema;
    }

    public function getCurrentSchema(): object|bool
    {
        return $this->schemaStack[$this->schemaStackPointer]['schema'];
    }

    public function getRootSchema(): object|bool
    {
        return $this->schemaStack[0]['schema'];
    }

    public function getCurrentKeywordLocation(int $length = 0): string
    {
        $location = $this->schemaStack[$this->schemaStackPointer]['keywordLocation'];

        if ($length === 0) {
            return $location;
        }

        return implode('/', array_slice(explode('/', $location), 0, $length));
    }

    public function getCurrentSchemaKeywordLocation(int $length = 0): string
    {
        $location = $this->schemaStack[$this->schemaStackPointer]['schemaKeywordLocation'];

        if ($length === 0) {
            return $location;
        }

        return implode('/', array_slice(explode('/', $location), 0, $length));
    }

    public function setCurrentBaseUri(string $baseUri, bool $withPrevious = true): void
    {
        $this->schemaStack[$this->schemaStackPointer]['baseUri'] = $baseUri;

        if ($withPrevious) {
            $this->schemaStack[$this->schemaStackPointer - 1]['baseUri'] = $baseUri;
        }
    }

    public function getCurrentBaseUri(): string
    {
        return $this->schemaStack[$this->schemaStackPointer]['baseUri'];
    }

    public function getCurrentAbsoluteKeywordLocation(): ?string
    {
        $currentStackEntry = $this->schemaStack[$this->schemaStackPointer];

        $baseUri = $currentStackEntry['baseUri'];
        if (!$baseUri) {
            return null;
        }

        if (str_contains($baseUri, '#')) {
            // Base URI is an anchor URI (e.g. http://www.example.com#anchor)
            return $baseUri . $currentStackEntry['schemaKeywordLocation'];
        }

        return $baseUri . '#' . $currentStackEntry['schemaKeywordLocation'];
    }
}