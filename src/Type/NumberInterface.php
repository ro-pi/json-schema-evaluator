<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Type;

interface NumberInterface extends TypeInterface
{
    const PRECISION = 16;

    function add(NumberInterface $addend): self;
    function sub(NumberInterface $subtrahend): self;
    function mul(NumberInterface $multiplicand): self;
    function div(NumberInterface $divisor): self;
    function pow(NumberInterface $exponent): self;
    function mod(NumberInterface $divisor): self;
    function sqrt(): self;
    function compare(NumberInterface $operand): int;
    function equals(NumberInterface $operand): bool;
    function greaterThan(NumberInterface $operand): bool;
    function lessThan(NumberInterface $operand): bool;
    function greaterThanOrEquals(NumberInterface $operand): bool;
    function lessThanOrEquals(NumberInterface $operand): bool;
    function isInteger(): bool;
}