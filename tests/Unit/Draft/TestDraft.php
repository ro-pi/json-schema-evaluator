<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Draft;

use Ropi\JsonSchemaEvaluator\Draft\AbstractDraft;
use Ropi\JsonSchemaEvaluator\Keyword\UnknownKeyword;

class TestDraft extends AbstractDraft
{
    public const VOCABULARY_1 = 'http://localhost/draft/test/vocab/1';
    public const VOCABULARY_2 = 'http://localhost/draft/test/vocab/2';

    protected array $vocabularies = [
        self::VOCABULARY_1 => true,
        self::VOCABULARY_2 => true,
    ];

    protected function registerKeywords(): void
    {
        $this->registerKeyword(new UnknownKeyword(1, 'foo'), self::VOCABULARY_1);
        $this->registerKeyword(new UnknownKeyword(2, 'bar'), self::VOCABULARY_2);
    }
}
