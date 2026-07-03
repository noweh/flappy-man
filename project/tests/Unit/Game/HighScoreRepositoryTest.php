<?php

namespace Tests\Unit\Game;

use App\Game\HighScoreRepository;
use PHPUnit\Framework\TestCase;

class HighScoreRepositoryTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/flappy_highscores_'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function test_empty_board(): void
    {
        $repository = new HighScoreRepository($this->path);

        $this->assertSame([], $repository->top());
        $this->assertNull($repository->bestScore('Earth'));
        $this->assertTrue($repository->qualifies(0, 'Earth'));
    }

    public function test_scores_are_sorted_persisted_and_filterable_by_world(): void
    {
        $repository = new HighScoreRepository($this->path);
        $repository->add('abc', 3, 'Earth');
        $repository->add('DEF', 10, 'Krypton');
        $repository->add('gh', 5, 'Earth');

        // Read through a fresh instance to prove persistence
        $fresh = new HighScoreRepository($this->path);

        $this->assertSame(['DEF', 'GH', 'ABC'], array_column($fresh->top(), 'initials'));
        $this->assertSame(['GH', 'ABC'], array_column($fresh->top('Earth'), 'initials'));
        $this->assertSame(['DEF'], array_column($fresh->top('Krypton'), 'initials'));
    }

    public function test_best_score_is_per_world(): void
    {
        $repository = new HighScoreRepository($this->path);
        $repository->add('AAA', 5, 'Earth');
        $repository->add('BBB', 12, 'Krypton');

        $this->assertSame(5, $repository->bestScore('Earth'));
        $this->assertSame(12, $repository->bestScore('Krypton'));
        $this->assertNull($repository->bestScore('Mars'));
    }

    public function test_initials_are_uppercased_and_truncated(): void
    {
        $repository = new HighScoreRepository($this->path);
        $repository->add('abcdef', 1, 'Earth');

        $this->assertSame('ABC', $repository->top()[0]['initials']);
    }

    public function test_each_world_keeps_its_own_top_ten(): void
    {
        $repository = new HighScoreRepository($this->path);
        foreach (range(1, 12) as $score) {
            $repository->add('E'.$score, $score, 'Earth');
        }
        $repository->add('KRY', 1, 'Krypton');

        $earth = $repository->top('Earth');

        $this->assertCount(10, $earth);
        $this->assertSame(12, $earth[0]['score']);
        $this->assertSame(3, $earth[9]['score']); // Earth 1 and 2 dropped
        $this->assertCount(1, $repository->top('Krypton')); // untouched by the Earth cap
    }

    public function test_qualification_is_per_world(): void
    {
        $repository = new HighScoreRepository($this->path);
        foreach (range(1, 10) as $score) {
            $repository->add('E'.$score, $score, 'Earth');
        }

        $this->assertFalse($repository->qualifies(1, 'Earth'));
        $this->assertTrue($repository->qualifies(2, 'Earth'));
        $this->assertTrue($repository->qualifies(0, 'Krypton')); // Krypton board is empty
    }
}
