<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\EvaluationConfig;

use Ropi\JsonSchemaEvaluator\Draft\DraftInterface;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPool;
use Ropi\JsonSchemaEvaluator\SchemaPool\SchemaPoolInterface;

class StaticEvaluationConfig
{
    private SchemaPoolInterface $schemaPool;

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
        private DraftInterface $defaultDraft,
        array $supportedDrafts = [],
        ?SchemaPoolInterface $schemaPool = null,
        private bool $acceptNumericStrings = false,
    ) {
        $this->addSupportedDraft($this->defaultDraft);

        foreach ($supportedDrafts as $supportedDraft) {
            if (!$supportedDraft instanceof DraftInterface) {
                throw new \InvalidArgumentException(
                    'Argument $supportedDraft must be an array of objects, which are implementing '
                    . DraftInterface::class
                    . ' interface',
                    1626044856
                );
            }

            $this->addSupportedDraft($supportedDraft);
        }

        if (!$schemaPool) {
            $this->schemaPool = new SchemaPool();
        }
    }

    public function getDefaultDraft(): DraftInterface
    {
        return $this->defaultDraft;
    }

    public function getSchemaPool(): SchemaPoolInterface
    {
        return $this->schemaPool;
    }

    public function getAcceptNumericStrings(): bool
    {
        return $this->acceptNumericStrings;
    }

    public function getSupportedDrafts(): array
    {
        return $this->supportedDrafts;
    }

    public function getSupportedDraftByUri(string $uri): ?DraftInterface
    {
        return $this->supportedDrafts[$uri] ?? null;
    }

    protected function addSupportedDraft(DraftInterface $draft): void
    {
        $this->supportedDrafts[$draft->getUri()] = $draft;
    }
}
