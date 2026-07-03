<?php

namespace Tests\Unit\Game;

use App\Game\Building;
use PHPUnit\Framework\TestCase;

class BuildingTest extends TestCase
{
    private function building(int $x = 50, int $holeY = 10): Building
    {
        return new Building($x, 3, $holeY, 25);
    }

    public function test_hole_rows_are_not_solid(): void
    {
        $building = $this->building(holeY: 10);

        foreach (range(7, 13) as $y) {
            $this->assertFalse($building->isSolidAt($y), "y=$y should be in the hole");
        }
        $this->assertTrue($building->isSolidAt(6));
        $this->assertTrue($building->isSolidAt(14));
    }

    public function test_borders_are_not_solid(): void
    {
        $building = $this->building();

        $this->assertFalse($building->isSolidAt(0));
        $this->assertFalse($building->isSolidAt(24));
        $this->assertTrue($building->isSolidAt(1));
        $this->assertTrue($building->isSolidAt(23));
    }

    public function test_rows_match_thickness_and_hole_is_blank(): void
    {
        $building = $this->building(holeY: 10);

        $this->assertSame(3, strlen($building->row(5)));
        $this->assertSame('   ', $building->row(10));
        $this->assertMatchesRegularExpression('/^[|V\[\]]{3}$/', $building->row(5));
    }

    public function test_moves_left_until_off_screen(): void
    {
        $building = $this->building(x: 2);

        $building->moveLeft(); // x = 1
        $this->assertFalse($building->isOffScreen());

        $building->moveLeft(); // x = 0
        $building->moveLeft(); // x = -1
        $building->moveLeft(); // x = -2
        $this->assertFalse($building->isOffScreen()); // still one visible column

        $building->moveLeft(); // x = -3
        $this->assertTrue($building->isOffScreen());
    }

    public function test_contains_x(): void
    {
        $building = $this->building(x: 50);

        $this->assertTrue($building->containsX(50));
        $this->assertTrue($building->containsX(52));
        $this->assertFalse($building->containsX(53));
        $this->assertFalse($building->containsX(49));
    }
}
