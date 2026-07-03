<?php

namespace App\Game;

class Engine
{
    public const WIDTH = 100;

    public const HEIGHT = 25;

    public const FLAPPY_X = 10;

    public const FLAPPY_WIDTH = 10;

    private const BUILDING_SPAWN_INTERVAL = 20;

    private const FLAP_STRENGTH = 5;

    // Collision column: deliberately deep inside the sprite (rear third) so a
    // building must visually overlap Flappy-man before it kills — forgiving on
    // purpose, a full-sprite hitbox makes the game frustrating
    private const HITBOX_X = self::FLAPPY_X + 7;

    private int $flappyY = 8;

    private int $frame = 0;

    private int $score = 0;

    /** @var Building[] */
    private array $buildings = [];

    private bool $over = false;

    /** 'building' or 'floor' once the game is over */
    private ?string $deathCause = null;

    /** @var callable(): Building */
    private $buildingFactory;

    public function __construct(
        private readonly World $world,
        ?callable $buildingFactory = null,
        private readonly int $width = self::WIDTH,
        private readonly int $height = self::HEIGHT,
    ) {
        $this->buildingFactory = $buildingFactory
            ?? fn (): Building => Building::random($this->width - 3, $this->height);
    }

    /**
     * Advance the game by one frame: spawn/move buildings, score, collide, apply gravity.
     */
    public function tick(): void
    {
        if ($this->over) {
            return;
        }

        if ($this->frame % self::BUILDING_SPAWN_INTERVAL === 0) {
            $this->buildings[] = ($this->buildingFactory)();
        }

        foreach ($this->buildings as $building) {
            $building->moveLeft();

            if (! $building->isPassed() && $building->x() + $building->thickness() <= self::FLAPPY_X) {
                $building->markPassed();
                $this->score++;
            }
        }

        $this->buildings = array_values(array_filter(
            $this->buildings,
            fn (Building $building): bool => ! $building->isOffScreen(),
        ));

        $this->frame++;

        if ($this->hitsBuilding()) {
            $this->over = true;
            $this->deathCause = 'building';

            return;
        }

        if ($this->flappyY >= $this->height - 2) {
            $this->flappyY = $this->height - 2;
            $this->over = true;
            $this->deathCause = 'floor';

            return;
        }

        $this->flappyY += $this->world->gravity;
    }

    public function flap(): void
    {
        if ($this->over) {
            return;
        }

        $this->flappyY -= min(self::FLAP_STRENGTH, $this->flappyY - 1);
    }

    public function buildingAt(int $x): ?Building
    {
        foreach ($this->buildings as $building) {
            if ($building->containsX($x)) {
                return $building;
            }
        }

        return null;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function flappyY(): int
    {
        return $this->flappyY;
    }

    public function frame(): int
    {
        return $this->frame;
    }

    public function score(): int
    {
        return $this->score;
    }

    public function isOver(): bool
    {
        return $this->over;
    }

    public function deathCause(): ?string
    {
        return $this->deathCause;
    }

    public function world(): World
    {
        return $this->world;
    }

    private function hitsBuilding(): bool
    {
        foreach ($this->buildings as $building) {
            if ($building->containsX(self::HITBOX_X) && $building->isSolidAt($this->flappyY)) {
                return true;
            }
        }

        return false;
    }
}
