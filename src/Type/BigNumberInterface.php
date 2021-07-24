<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Type;

interface BigNumberInterface extends TypeInterface
{
    const PRECISION = 16;

    function add(BigNumberInterface $addend): self;
    function sub(BigNumberInterface $subtrahend): self;
    function mul(BigNumberInterface $multiplicand): self;
    function div(BigNumberInterface $divisor): self;
    function pow(BigNumberInterface $exponent): self;
    function mod(BigNumberInterface $divisor): self;
    function sqrt(): self;
    function compare(BigNumberInterface $operand): int;
    function equals(BigNumberInterface $operand): bool;
    function greaterThan(BigNumberInterface $operand): bool;
    function lessThan(BigNumberInterface $operand): bool;
    function greaterThanOrEquals(BigNumberInterface $operand): bool;
    function lessThanOrEquals(BigNumberInterface $operand): bool;
    function isInteger(): bool;
}