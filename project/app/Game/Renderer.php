<?php

namespace App\Game;

class Renderer
{
    private const COLORS = [
        'white' => "\033[37m",
        'red' => "\033[31m",
        'blue' => "\033[34m",
        'maroon' => "\033[35m",
        'yellow' => "\033[33m",
        'green' => "\033[32m",
        'reset' => "\033[0m",
    ];

    private const SUN_PATTERN = [
        [0, 1, 1, 1, 0],
        [1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [0, 1, 1, 1, 0],
    ];

    private array $flappyManIntroductionArray;

    private array $flappyManAlive;

    private string $flappyManDead;

    private string $flappyManCrashed;

    public function __construct()
    {
        $colors = self::COLORS;

        $this->flappyManIntroductionArray = [
            "             {$colors['white']}.=.,{$colors['reset']}",
            "            {$colors['white']};c =\ {$colors['reset']}",
            "          {$colors['red']}__{$colors['white']}|  _/{$colors['reset']}",
            "        {$colors['blue']}.{$colors['red']}'{$colors['blue']}-{$colors['red']}'{$colors['blue']}-._{$colors['red']}/{$colors['blue']}-{$colors['red']}'{$colors['blue']}-._{$colors['reset']}",
            "       {$colors['blue']}/..   {$colors['red']}____    {$colors['blue']}\ {$colors['reset']}",
            "      {$colors['blue']}/' _  {$colors['red']}[ ---] {$colors['blue']})  \ {$colors['reset']}",
            "     {$colors['blue']}(  / \--{$colors['red']}\_|¯ {$colors['blue']}-/'. ){$colors['reset']}",
            "      {$colors['blue']}\-;_/}\__;__/ _/ _/{$colors['reset']}",
            "       {$colors['blue']}'.{$colors['white']}_}{$colors['blue']}|==o==\\{$colors['white']}{_{$colors['blue']}\/{$colors['reset']}",
        ];

        $this->flappyManAlive = [
            "{$colors['red']},_\"°{$colors['blue']},^{$colors['red']}>{$colors['white']}O{$colors['blue']}_{$colors['white']},{$colors['reset']}",
            "{$colors['red']},_`¯{$colors['blue']},^{$colors['red']}>{$colors['white']}O{$colors['blue']}_{$colors['white']},{$colors['reset']}",
            "{$colors['red']},_^\"{$colors['blue']},^{$colors['red']}>{$colors['white']}O{$colors['blue']}_{$colors['white']},{$colors['reset']}",
        ];

        $this->flappyManDead = "{$colors['red']}___n~\_O_/{$colors['reset']}";
        $this->flappyManCrashed = "     {$colors['red']}<O|XX{$colors['reset']}";
    }

    public function draw(string $screen): void
    {
        // Home the cursor and overwrite the previous frame in place: every line ends
        // with \033[K and the frame ends with \033[J, so leftovers are erased without
        // the full-screen clear that makes the terminal flicker
        echo "\033[H".$screen."\033[J";
    }

    public function introduction(): string
    {
        $colors = self::COLORS;

        $buffer = $this->bufferLine().$this->bufferLine();
        foreach ($this->flappyManIntroductionArray as $flappyManIntroduction) {
            $buffer .= $this->greenLine('          '.$flappyManIntroduction);
        }

        $buffer .= $this->greenLine("               {$colors['white']}Welcome to Flappy-man!{$colors['reset']}");
        $buffer .= str_repeat($this->bufferLine(), 4);
        $buffer .= $this->greenLine("          Press {$colors['white']}'ENTER'{$colors['green']} to start the game, {$colors['white']}'S'{$colors['green']} to see the high scores, or {$colors['white']}'Q'{$colors['green']} to quit...");

        return $buffer;
    }

    public function worldSelection(World $world): string
    {
        $colors = self::COLORS;
        $artColor = $colors[$world->artColor];

        $buffer = $this->bufferLine().$this->bufferLine();
        $buffer .= $this->greenLine("               {$colors['white']}Earth{$colors['reset']} or {$colors['white']}Krypton{$colors['reset']}");
        $buffer .= $this->bufferLine().$this->bufferLine();
        foreach ($world->art as $artLine) {
            $buffer .= $this->greenLine('               '.$artColor.$artLine.$colors['reset']);
        }
        $buffer .= $this->bufferLine().$this->bufferLine();
        $buffer .= $this->greenLine('                       '.$colors['white'].$world->name.$colors['reset']);
        $buffer .= $this->bufferLine().$this->bufferLine();
        $buffer .= $this->greenLine("          Select a Planet with your {$colors['white']}'Arrow Keys'{$colors['green']}");
        $buffer .= $this->bufferLine().$this->bufferLine();
        $buffer .= $this->greenLine("          Press {$colors['white']}'ENTER'{$colors['green']} to start the game, or {$colors['white']}'Q'{$colors['green']} to quit...");

        return $buffer;
    }

    public function game(Engine $engine, bool $paused = false, ?int $best = null): string
    {
        $colors = self::COLORS;
        $world = $engine->world();
        $buildingsColor = $colors[$world->buildingsColor];
        $sunColor = $colors[$world->sunColor];

        $buffer = $this->bufferLine();
        $buffer .= $this->greenLine("          {$colors['blue']}How to play:{$colors['green']} {$colors['white']}'SPACE'{$colors['green']} to fly up, {$colors['white']}'P'{$colors['green']} to pause, {$colors['white']}'R'{$colors['green']} to restart, {$colors['white']}'Q'{$colors['green']} to quit.");
        $buffer .= $this->bufferLine();

        $sunWidth = count(self::SUN_PATTERN[0]);
        $sunHeight = count(self::SUN_PATTERN);
        $sunX = $engine->width() - 8;
        $sunY = 2;

        for ($y = 0; $y < $engine->height(); $y++) {
            for ($x = 0; $x < $engine->width(); $x++) {
                $isFlappyManPosition = $x === Engine::FLAPPY_X && $y === $engine->flappyY();
                $isTopOrBottomBorder = $y === 0 || $y === $engine->height() - 1;
                $isRightBorder = $x === $engine->width() - 1;
                $isSunPosition = $x >= $sunX && $x < $sunX + $sunWidth
                    && $y >= $sunY && $y < $sunY + $sunHeight
                    && self::SUN_PATTERN[$y - $sunY][$x - $sunX] === 1;

                $building = $engine->buildingAt($x);

                if ($isFlappyManPosition) {
                    $buffer .= $this->flappySprite($engine);
                    $x += Engine::FLAPPY_WIDTH - 1; // Skip the cells covered by the sprite
                } elseif ($building?->isSolidAt($y)) {
                    // Draw the visible part of the row in one go, then skip its cells
                    $visible = substr($building->row($y), $x - $building->x());
                    $buffer .= $buildingsColor.$visible.$colors['reset'];
                    $x += strlen($visible) - 1;
                } elseif ($isSunPosition) {
                    $buffer .= "{$sunColor}*{$colors['reset']}";
                } elseif ($isTopOrBottomBorder) {
                    $buffer .= '#';
                } elseif ($isRightBorder) {
                    $buffer .= '||';
                } else {
                    $buffer .= ' ';
                }
            }
            $buffer .= "\033[K".PHP_EOL;
        }

        if ($paused) {
            $status = "{$colors['white']}PAUSED{$colors['green']} — press 'P' to resume";
        } else {
            $status = 'score : '.$engine->score();
            if ($best !== null) {
                $status .= "   |   best : {$colors['white']}{$best}{$colors['green']}";
            }
        }
        $buffer .= $this->greenLine($status);

        return $buffer;
    }

    public function gameOver(Engine $engine, bool $qualifies, string $initials): string
    {
        $colors = self::COLORS;

        $buffer = str_repeat($this->bufferLine(), 8);
        $buffer .= $this->greenLine("                    {$colors['red']}GAME OVER!{$colors['reset']}");
        $buffer .= $this->bufferLine();
        $buffer .= $this->greenLine("                    Your score: {$colors['white']}{$engine->score()}{$colors['green']} ({$engine->world()->name})");
        $buffer .= $this->bufferLine().$this->bufferLine();

        if ($qualifies) {
            $buffer .= $this->greenLine("                    {$colors['yellow']}NEW HIGH SCORE!{$colors['reset']}");
            $buffer .= $this->bufferLine();
            $buffer .= $this->greenLine("                    Type your initials: {$colors['white']}".str_pad($initials, 3, '_')."{$colors['reset']}");
            $buffer .= $this->greenLine("                    Press {$colors['white']}'ENTER'{$colors['green']} to save (empty to skip)");
        } else {
            $buffer .= $this->greenLine("          Press {$colors['white']}'R'{$colors['green']} to restart, {$colors['white']}'S'{$colors['green']} to see the high scores, {$colors['white']}'Q'{$colors['green']} to quit...");
        }

        return $buffer;
    }

    /**
     * @param  array<int, array{initials: string, score: int, world: string}>  $scores
     */
    public function scoreboard(World $world, array $scores): string
    {
        $colors = self::COLORS;
        $worldColor = $colors[$world->artColor];

        $buffer = str_repeat($this->bufferLine(), 2);
        $buffer .= $this->greenLine("                    {$colors['white']}HIGH SCORES{$colors['reset']}   <- {$worldColor}{$world->name}{$colors['green']} ->");
        $buffer .= $this->bufferLine();

        if ($scores === []) {
            $buffer .= $this->greenLine("                    No scores yet on {$world->name}, be the first!");
        }

        foreach ($scores as $rank => $entry) {
            $buffer .= $this->greenLine(sprintf(
                "                    %2d. {$colors['white']}%-3s{$colors['green']} %5d",
                $rank + 1,
                $entry['initials'],
                $entry['score'],
            ));
        }

        $buffer .= $this->bufferLine().$this->bufferLine();
        $buffer .= $this->greenLine("          Switch world with your {$colors['white']}'Arrow Keys'{$colors['green']}");
        $buffer .= $this->greenLine("          Press {$colors['white']}'R'{$colors['green']} to play, {$colors['white']}'ENTER'{$colors['green']} for the menu, {$colors['white']}'Q'{$colors['green']} to quit...");

        return $buffer;
    }

    private function flappySprite(Engine $engine): string
    {
        if (! $engine->isOver()) {
            return $this->flappyManAlive[$engine->frame() % 3];
        }

        return $engine->deathCause() === 'building' ? $this->flappyManCrashed : $this->flappyManDead;
    }

    private function bufferLine(string $text = ''): string
    {
        return $text."\033[K".PHP_EOL;
    }

    private function greenLine(string $text): string
    {
        // Same green base color as Command::info() applied to direct output
        return $this->bufferLine(self::COLORS['green'].$text.self::COLORS['reset']);
    }
}
