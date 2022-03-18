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
     */
    public function __construct(
        public /*readonly*/ DraftInterface $defaultDraft,
        array $supportedDrafts = [],
        ?SchemaPoolInterface $schemaPool = null
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

    public function getSupportedDraftByUri(string $uri): ?DraftInterface
    {
        return $this->supportedDrafts[$uri] ?? null;
    }
}
