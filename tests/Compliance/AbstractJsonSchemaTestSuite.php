<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Tests\Compliance;

use PHPUnit\Framework\TestCase;
use Ropi\JsonSchemaEvaluator\EvaluationConfig\StaticEvaluationConfig;
use Ropi\JsonSchemaEvaluator\EvaluationContext\RuntimeEvaluationResult;
use Ropi\JsonSchemaEvaluator\JsonSchemaEvaluator;
use Ropi\JsonSchemaEvaluator\Output\BasicOutput;

abstract class AbstractJsonSchemaTestSuite extends TestCase
{
    protected JsonSchemaEvaluator $jsonSchemaValidator;

    public function setUp(): void
    {
        $this->jsonSchemaValidator = new JsonSchemaEvaluator();
    }

    abstract protected function getRelativeTestsPath(): string;

    /**
     * @return array<array<\stdClass>>
     */
    public function jsonSchemaTestSuiteProvider(): array
    {
        $data = [];

        foreach ($this->getFileCollection() as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo
                || $fileInfo->isDir()
                || $fileInfo->getExtension() !== 'json'
            ) {
                continue;
            }

            $testJson = file_get_contents($fileInfo->getPathname());
            if (!is_string($testJson)) {
                throw new \RuntimeException(
                    'Can not read JSON schema test suite file: '
                    . $fileInfo->getPathname()
                );
            }

            $testCollections = json_decode($testJson, false, 512, JSON_BIGINT_AS_STRING);
            if (!is_array($testCollections)) {
                throw new \RuntimeException(
                    'Can not parse JSON schema test suite file: '
                    . $fileInfo->getPathname()
                );
            }

            foreach ($testCollections as $testCollection) {
                $data[] = [$testCollection];
            }
        }

        return $data;
    }

    /**
     * @throws \Ropi\JsonSchemaEvaluator\Keyword\Exception\StaticKeywordAnalysisException
     */
    protected function evaluateTestCollection(
        \stdClass $testCollection,
        StaticEvaluationConfig $staticEvaluationConfig
    ): void {
        $staticEvaluationContext = $this->jsonSchemaValidator->evaluateStatic(
            $testCollection->schema,
            $staticEvaluationConfig
        );

        foreach ($testCollection->tests as $test) {
            $instance = is_object($test->data) ? clone $test->data : $test->data;

            $valid = $this->jsonSchemaValidator->evaluate(
                $instance,
                $staticEvaluationContext,
                $results
            );

            $this->assertExpectedResult($valid, $results, $testCollection, $test);

            if (property_exists($test, 'mutatedData')) {
                $this->assertJsonStringEqualsJsonString(
                    (string)json_encode($test->mutatedData),
                    (string)json_encode($instance),
                    'Mutation test failed'
                    . PHP_EOL
                    . 'Test Collection: '
                    . $testCollection->description
                    . PHP_EOL
                    . 'Test Case: '
                    . $test->description
                );
            }
        }
    }

    /**
     * @param RuntimeEvaluationResult[] $results
     */
    protected function assertExpectedResult(bool $valid, array $results, \stdClass $testCollection, \stdClass $test): void
    {
        $this->assertEquals(
            $test->valid,
            $valid,
            'Schema: '
            . PHP_EOL
            . json_encode($testCollection->schema, JSON_PRETTY_PRINT)
            . PHP_EOL
            . PHP_EOL
            . 'Instance: '
            . PHP_EOL
            . json_encode($test->data, JSON_PRETTY_PRINT)
            . PHP_EOL
            . PHP_EOL
            . 'Validation result: '
            . PHP_EOL
            . json_encode((new BasicOutput($valid, $results))->format(), JSON_PRETTY_PRINT)
            . PHP_EOL
            . PHP_EOL
            . 'Test Collection: '
            . $testCollection->description
            . PHP_EOL
            . 'Test Case: '
            . $test->description
        );
    }

    /**
     * @return \Traversable<\SplFileInfo>
     */
    protected function getFileCollection(): \Traversable
    {
        $testSuiteDir = __DIR__ . '/../Resources/json-schema-test-suite/tests';
        $path = $testSuiteDir . '/' . $this->getRelativeTestsPath();

        if (is_dir($path)) {
            return new \DirectoryIterator(
                $testSuiteDir
                . '/'
                . $this->getRelativeTestsPath()
            );
        } else if (is_file($path)) {
            return new \ArrayObject([new \SplFileInfo($path)]);
        }

        throw new \RuntimeException(
            'Test file path \''
            . $path
            . '\' does not exist'
        );
    }
}
