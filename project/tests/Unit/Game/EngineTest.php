<?php

namespace Tests\Unit\Game;

use App\Game\Building;
use App\Game\Engine;
use App\Game\World;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    private function engine(?callable $buildingFactory = null, string $world = 'earth'): Engine
    {
        return new Engine(World::all()[$world], $buildingFactory);
    }

    private function tickUntilOver(Engine $engine, int $maxTicks = 100): int
    {
        for ($i = 1; $i <= $maxTicks; $i++) {
            $engine->tick();
            if ($engine->isOver()) {
                return $i;
            }
        }

        $this->fail("Game not over after $maxTicks ticks");
    }

    public function test_gravity_pulls_flappy_down_to_floor_death(): void
    {
        $engine = $this->engine();

        $this->assertSame(8, $engine->flappyY());
        $engine->tick();
        $this->assertSame(9, $engine->flappyY());

        $this->tickUntilOver($engine, 20);

        $this->assertSame('floor', $engine->deathCause());
        $this->assertSame(Engine::HEIGHT - 2, $engine->flappyY());
        $this->assertSame(0, $engine->score());
    }

    public function test_krypton_gravity_is_faster_and_still_dies_on_floor(): void
    {
        $earthTicks = $this->tickUntilOver($this->engine());
        $kryptonTicks = $this->tickUntilOver($this->engine(world: 'krypton'));

        $this->assertLessThan($earthTicks, $kryptonTicks);
        $this->assertSame(Engine::HEIGHT - 2, $this->engineAfterFloorDeath('krypton')->flappyY());
    }

    private function engineAfterFloorDeath(string $world): Engine
    {
        $engine = $this->engine(world: $world);
        $this->tickUntilOver($engine);

        return $engine;
    }

    public function test_flap_moves_up_and_never_leaves_the_grid(): void
    {
        $engine = $this->engine();

        $engine->flap();
        $this->assertSame(3, $engine->flappyY());

        $engine->flap();
        $this->assertSame(1, $engine->flappyY()); // clamped below the top border

        $engine->flap();
        $this->assertSame(1, $engine->flappyY());
    }

    public function test_dies_when_hitting_a_building(): void
    {
        // Building close to Flappy-man with a hole far below his fall path
        $engine = $this->engine(fn () => new Building(25, 3, 20, Engine::HEIGHT));

        $ticks = $this->tickUntilOver($engine, 20);

        $this->assertSame('building', $engine->deathCause());
        $this->assertSame(0, $engine->score());
        // Forgiving hitbox: death only when the building reaches column x=17
        // (rear third of the sprite), 8 ticks after spawning at x=25
        $this->assertSame(8, $ticks);
    }

    public function test_scores_when_passing_through_a_building_hole(): void
    {
        // Building whose hole (y 5..11) covers the fall path while it crosses the hitbox
        $engine = $this->engine(fn () => new Building(12, 3, 8, Engine::HEIGHT));

        for ($i = 0; $i < 5; $i++) {
            $engine->tick();
        }

        $this->assertFalse($engine->isOver());
        $this->assertSame(1, $engine->score());
    }

    public function test_grid_dimensions_are_configurable(): void
    {
        $engine = new Engine(World::all()['earth'], width: 60, height: 20);

        $this->assertSame(60, $engine->width());
        $this->assertSame(20, $engine->height());

        $this->tickUntilOver($engine, 20);

        $this->assertSame('floor', $engine->deathCause());
        $this->assertSame(18, $engine->flappyY()); // floor row follows the custom height
    }

    public function test_flap_is_ignored_once_over(): void
    {
        $engine = $this->engine();
        $this->tickUntilOver($engine);

        $y = $engine->flappyY();
        $engine->flap();

        $this->assertSame($y, $engine->flappyY());
    }
}
