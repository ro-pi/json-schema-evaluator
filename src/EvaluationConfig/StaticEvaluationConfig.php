<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationConfig;

use Ropi\JsonSchemaEvaluator\Draft\DraftInterface;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPool;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPoolInterface;

class StaticEvaluationConfig
{
    public /*readonly*/ SchemaPoolInterface $schemaPool;

    /**
     * @var DraftInterface[]
     */
    private array $supportedDrafts = [];

    /**
     * @param DraftInterface $defaultDraft
     * @param DraftInterface[] $supportedDrafts
     * @param SchemaPoolInterface|null $schemaPool
     * @param bool $acceptNumericStrings
     */
    public function __construct(
        public /*readonly*/ DraftInterface $defaultDraft,
        array $supportedDrafts = [],
        ?SchemaPoolInterface $schemaPool = null,
        public /*readonly*/ bool $acceptNumericStrings = false,
    ) {
        $this->supportedDrafts[$this->defaultDraft->getUri()] = $this->defaultDraft;

        foreach ($supportedDrafts as $supportedDraft) {
            if (!$supportedDraft instanceof DraftInterface) {
                throw new \InvalidArgumentException(
                    'Argument $supportedDraft must be an array of objects, which are implementing '
                    . DraftInterface::class
                    . ' interface',
                    1626044856
                );
            }

            $this->supportedDrafts[$supportedDraft->getUri()] = $supportedDraft;
        }

        $this->schemaPool = $schemaPool ?? new SchemaPool();
    }

    public function getSupportedDrafts(): array
    {
        return $this->supportedDrafts;
    }

    public function getSupportedDraftByUri(string $uri): ?DraftInterface
    {
        return $this->supportedDrafts[$uri] ?? null;
    }
}
