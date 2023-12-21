<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Output;

/**
 * @noinspection PhpUnused
 */
class FlagOutput extends AbstractOutput
{
    public function format(): \stdClass
    {
        return (object)[
            'valid' => $this->getValid()
        ];
    }
}