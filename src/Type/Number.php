<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Type;

class Number implements NumberInterface
{
    protected string $value;

    public function __construct(string $value)
    {
        $value = $this->parseBcMathNumber($value);

        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                '$value is not a well-formed number',
                1626033354
            );
        }

        $this->value = $value;
    }

    public function add(NumberInterface $addend): self
    {
        $this->value = \bcadd($this->value, (string)$addend, static::PRECISION);
        return $this;
    }

    public function sub(NumberInterface $subtrahend): self
    {
        $this->value = \bcsub($this->value, (string)$subtrahend, static::PRECISION);
        return $this;
    }

    public function mul(NumberInterface $multiplicand): self
    {
        $this->value = \bcmul($this->value, (string)$multiplicand, static::PRECISION);
        return $this;
    }

    public function div(NumberInterface $divisor): self
    {
        $this->value = \bcdiv($this->value, (string)$divisor, static::PRECISION);
        return $this;
    }

    public function pow(NumberInterface $exponent): self
    {
        $this->value = \bcpow($this->value, (string)$exponent, static::PRECISION);
        return $this;
    }

    public function mod(NumberInterface $divisor): self
    {
        $this->value = \bcmod($this->value, (string)$divisor, static::PRECISION);
        return $this;
    }

    public function sqrt(): self
    {
        $this->value = \bcsqrt($this->value, static::PRECISION);
        return $this;
    }

    public function compare(NumberInterface $operand): int
    {
        return \bccomp($this->value, (string)$operand, static::PRECISION);
    }

    public function equals(NumberInterface $operand): bool
    {
        return $this->compare($operand) === 0;
    }

    public function greaterThan(NumberInterface $operand): bool
    {
        return $this->compare($operand) === 1;
    }

    public function lessThan(NumberInterface $operand): bool
    {
        return $this->compare($operand) === -1;
    }

    public function greaterThanOrEquals(NumberInterface $operand): bool
    {
        return $this->compare($operand) >= 0;
    }

    public function lessThanOrEquals(NumberInterface $operand): bool
    {
        return $this->compare($operand) <= 0;
    }

    public function isInteger(): bool
    {
        $parts = explode('.', $this->value, 2);

        if (!isset($parts[1])) {
            return true;
        }

        return \bccomp($parts[1], '0') === 0;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private function parseBcMathNumber(string $string): ?string
    {
        try {
            return \bcadd($string, '0', static::PRECISION);
        } catch (\ValueError) {
            // not well-formed number
        }

        return null;
    }
}