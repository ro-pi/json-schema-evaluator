<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Unit\Keyword\Exception;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\Draft\Draft202012;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationContext;
use Ropi\JsonSchemaEvaluator\EvaluationContext\StaticEvaluationContext;
use Ropi\JsonSchemaEvaluator\Keyword\Exception\KeywordRuntimeEvaluationException;
use Ropi\JsonSchemaEvaluator\Keyword\UnknownKeyword;

class KeywordRuntimeEvaluationExceptionTest extends TestCase
{
    public function test(): void
    {
        $keyword = new UnknownKeyword(1, 'test');
        $instance = 123;
        $staticContext = new StaticEvaluationContext(true, new StaticEvaluationConfig(defaultDraft: new Draft202012()));
        $runtimeContext = new RuntimeEvaluationContext(true, $instance, $staticContext);

        $exception = new KeywordRuntimeEvaluationException('test777', $keyword, $runtimeContext);

        $this->assertEquals('test777', $exception->getMessage());
        $this->assertEquals($keyword, $exception->getKeyword());
        $this->assertEquals($runtimeContext, $exception->getContext());
    }
}
