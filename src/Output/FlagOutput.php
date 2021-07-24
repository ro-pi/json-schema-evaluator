<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Output;

class FlagOutput extends AbstractOutput
{
    public function format(): object
    {
        return (object) [
            'valid' => $this->getValid()
        ];
    }
}