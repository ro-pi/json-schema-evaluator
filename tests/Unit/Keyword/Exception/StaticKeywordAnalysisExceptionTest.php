<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Keyword\Exception;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException;
use Ropi\JsonSchemaEvaluator\Keyword\ReservedLocation\CommentKeyword;

class StaticKeywordAnalysisExceptionTest extends TestCase
{
    public function test(): void
    {
        $keyword = new CommentKeyword(1);
        $staticContext = new StaticEvaluationContext(true, new StaticEvaluationConfig(defaultDraft: new Draft202012()));

        $exception = new StaticKeywordAnalysisException('test123', $keyword, $staticContext);

        $this->assertEquals('test123', $exception->getMessage());
        $this->assertEquals($keyword, $exception->getKeyword());
        $this->assertEquals($staticContext, $exception->getContext());
        $this->assertEquals('', $exception->getKeywordLocation());
    }
}
