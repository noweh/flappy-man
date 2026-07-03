<?php

namespace App\Game;

class Building
{
    public const HOLE_HALF_HEIGHT = 3;

    private const CHARS = ['|', 'V', '[', ']'];

    /** @var array<int, string> pre-rendered rows indexed by y, hole rows are spaces */
    private array $rows = [];

    private bool $passed = false;

    public function __construct(
        private int $x,
        private readonly int $thickness,
        private readonly int $holeY,
        int $gridHeight,
    ) {
        for ($y = 1; $y < $gridHeight - 1; $y++) {
            $row = '';
            for ($i = 0; $i < $thickness; $i++) {
                $row .= $this->isHole($y) ? ' ' : self::CHARS[array_rand(self::CHARS)];
            }
            $this->rows[$y] = $row;
        }
    }

    public static function random(int $x, int $gridHeight): self
    {
        return new self($x, 3, rand(5, $gridHeight - 5), $gridHeight);
    }

    public function moveLeft(): void
    {
        $this->x--;
    }

    public function isOffScreen(): bool
    {
        return $this->x + $this->thickness <= 0;
    }

    public function containsX(int $x): bool
    {
        return $x >= $this->x && $x < $this->x + $this->thickness;
    }

    public function isSolidAt(int $y): bool
    {
        return isset($this->rows[$y]) && ! $this->isHole($y);
    }

    public function row(int $y): string
    {
        return $this->rows[$y] ?? str_repeat(' ', $this->thickness);
    }

    public function x(): int
    {
        return $this->x;
    }

    public function thickness(): int
    {
        return $this->thickness;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function markPassed(): void
    {
        $this->passed = true;
    }

    private function isHole(int $y): bool
    {
        return $y >= $this->holeY - self::HOLE_HALF_HEIGHT && $y <= $this->holeY + self::HOLE_HALF_HEIGHT;
    }
}
