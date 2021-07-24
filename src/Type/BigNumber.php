<?php
declare(strict_types=1);

namespace Ropi\JsonSchemaEvaluator\Type;

class BigNumber implements BigNumberInterface
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

    public function add(BigNumberInterface $addend): self
    {
        $this->value = bcadd($this->value, (string) $addend, static::PRECISION);
        return $this;
    }

    public function sub(BigNumberInterface $subtrahend): self
    {
        $this->value = bcsub($this->value, (string) $subtrahend, static::PRECISION);
        return $this;
    }

    public function mul(BigNumberInterface $multiplicand): self
    {
        $this->value = bcmul($this->value, (string) $multiplicand, static::PRECISION);
        return $this;
    }

    public function div(BigNumberInterface $divisor): self
    {
        $this->value = bcdiv($this->value, (string) $divisor, static::PRECISION);
        return $this;
    }

    public function pow(BigNumberInterface $exponent): self
    {
        $this->value = bcpow($this->value, (string) $exponent, static::PRECISION);
        return $this;
    }

    public function mod(BigNumberInterface $divisor): self
    {
        $this->value = bcmod($this->value, (string) $divisor, static::PRECISION);
        return $this;
    }

    public function sqrt(): self
    {
        $this->value = bcsqrt($this->value, static::PRECISION);
        return $this;
    }

    public function compare(BigNumberInterface $operand): int
    {
        return bccomp($this->value, (string) $operand, static::PRECISION);
    }

    public function equals(BigNumberInterface $operand): bool
    {
        return $this->compare($operand) === 0;
    }

    public function greaterThan(BigNumberInterface $operand): bool
    {
        return $this->compare($operand) === 1;
    }

    public function lessThan(BigNumberInterface $operand): bool
    {
        return $this->compare($operand) === -1;
    }

    public function greaterThanOrEquals(BigNumberInterface $operand): bool
    {
        return $this->compare($operand) >= 0;
    }

    public function lessThanOrEquals(BigNumberInterface $operand): bool
    {
        return $this->compare($operand) <= 0;
    }

    public function isInteger(): bool
    {
        $parts = explode('.', $this->value, 2);

        if (!isset($parts[1])) {
            return true;
        }

        return bccomp($parts[1], '0') === 0;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    protected function parseBcMathNumber(string $string): ?string
    {
        try {
            return bcadd($string, '0', static::PRECISION);
        } catch (\Throwable) {
            // not well-formed number
        }

        return null;
    }
}