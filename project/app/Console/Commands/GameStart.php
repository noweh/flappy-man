<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use function Laravel\Prompts\pause;

class GameStart extends Command
{
    protected $signature = 'game:start';
    protected $description = 'Start the game';

    private const SPACE_KEY = ' ';
    private const QUIT_KEY = 'q';

    private array $colors = [
        'white' => "\033[37m",
        'red' => "\033[31m",
        'blue' => "\033[34m",
        'yellow' => "\033[33m",
        'green' => "\033[32m",
        'reset' => "\033[0m",
    ];

    private array $flappyManIntroductionArray = [];
    private array $flappyManAlive = [];
    private string $flappyManDead;
    private array $gridDimensions = [
        'width' => 100,
        'height' => 25
    ];

    private array $flappyManPosition = [
        'x' => 10,
        'y' => 8
    ];

    private array $sunPattern = [
        [0, 1, 1, 1, 0],
        [1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [0, 1, 1, 1, 0],
    ];

    private array $buildings = [];

    private string $sunColor;

    private int $gravity;

    public function __construct()
    {
        parent::__construct();

        $this->flappyManIntroductionArray = [
            "             {$this->colors['white']}.=.,{$this->colors['reset']}",
            "            {$this->colors['white']};c =\ {$this->colors['reset']}",
            "          {$this->colors['red']}__{$this->colors['white']}|  _/{$this->colors['reset']}",
            "        {$this->colors['blue']}.{$this->colors['red']}'{$this->colors['blue']}-{$this->colors['red']}'{$this->colors['blue']}-._{$this->colors['red']}/{$this->colors['blue']}-{$this->colors['red']}'{$this->colors['blue']}-._{$this->colors['reset']}",
            "       {$this->colors['blue']}/..   {$this->colors['red']}____    {$this->colors['blue']}\ {$this->colors['reset']}",
            "      {$this->colors['blue']}/' _  {$this->colors['red']}[ ---] {$this->colors['blue']})  \ {$this->colors['reset']}",
            "     {$this->colors['blue']}(  / \--{$this->colors['red']}\_|¯/{$this->colors['blue']}-/'. ){$this->colors['reset']}",
            "      {$this->colors['blue']}\-;_/}\__;__/ _/ _/{$this->colors['reset']}",
            "       {$this->colors['blue']}'.{$this->colors['white']}_}{$this->colors['blue']}|==o==\\{$this->colors['white']}{_{$this->colors['blue']}\/{$this->colors['reset']}"
        ];

        $this->flappyManAlive = [
            "{$this->colors['red']},_\"°{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']}.{$this->colors['reset']}",
            "{$this->colors['red']},_`¯{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']}.{$this->colors['reset']}",
            "{$this->colors['red']},_^\"{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']}.{$this->colors['reset']}",
        ];

        $this->flappyManDead = "{$this->colors['red']}___n~\_O_/{$this->colors['reset']}";

        $this->gravity = 1;
        $this->sunColor = $this->colors['yellow'];
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->drawIntroduction();
        pause('Press "ENTER" to start the game...');

        $loop = Loop::get();
        $this->drawGrid($loop);
        $this->detectInput($loop);

        try {
            $loop->run();
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        } finally {
            // Restore terminal settings
            system('stty -cbreak echo');
        }
    }

    private function drawIntroduction(): void
    {
        $this->clearGrid();
        $this->info(PHP_EOL);
        foreach ($this->flappyManIntroductionArray as $flappyManIntroduction) {
            $this->info($flappyManIntroduction);
        }

        $this->info("     {$this->colors['white']}Welcome to Flappy-man!{$this->colors['reset']}");
        $this->info(PHP_EOL);
        $this->info(PHP_EOL);
        $this->info(PHP_EOL);
    }

    private function drawGrid(LoopInterface $loop): void
    {
        $frame = 0;

        $loop->addPeriodicTimer(0.1, function () use (&$frame, $loop) {
            $this->clearGrid();
            $this->drawFrame($frame, $loop);
            $frame++;
        });
    }

    private function clearGrid(): void
    {
        echo "\033[H\033[J"; // Clear the screen and move cursor to the top-left corner
    }

    private function generateBuilding(): void
    {
        $height = $this->gridDimensions['height'];
        $width = $this->gridDimensions['width'];
        $holePosition = rand(5, $height - 5);

        $building = new \stdClass();
        $building->width = $width - 1; // Start building inside the grid
        $building->thickness = 3;
        $building->render = [];

        for ($y = 1; $y < $height-1; $y++) {
            $row = '';
            for ($x = 0; $x < $building->thickness; $x++) {
                if ($y >= $holePosition - 3 && $y <= $holePosition + 3) {
                    $row .= ' '; // Hole for Flappy-man to fly through
                } else {
                    // Chuffle the building pattern
                    $char = ['|', 'V', '[', ']'];
                    $row .= $char[array_rand($char)];
                }
            }
            $building->render[$y] = $row;
        }

        $this->buildings[] = $building;
    }

    private function moveBuildings(): void
    {
        foreach ($this->buildings as $building) {
            --$building->width;
        }

        // Remove the building if it is out of the grid
        $this->buildings = array_filter($this->buildings, function ($building) {
            return $building->width+$building->thickness > 0;
        });
    }

    private function getBuildingChar(int $x, int $y): ?string
    {
        foreach ($this->buildings as $building) {
            // Vérifier si le x est dans la plage du bâtiment (épaisseur incluse)
            if ($x >= $building->width && $x < $building->width + $building->thickness) {
                // Si oui, retourne le caractère correspondant à la ligne du bâtiment
                return $building->render[$y] ?? null; // Si $y est en dehors de la hauteur du bâtiment, retourne null
            }
        }


        return null; // Pas de bâtiment à cette position
    }

    private function drawFrame(int $frame, $loop): void
    {
        echo PHP_EOL;
        $this->info("          {$this->colors['blue']}How to play:{$this->colors['green']} Press {$this->colors['white']}'SPACE' {$this->colors['green']} to make Flappy fly up, {$this->colors['white']}'Q'{$this->colors['green']} to quit the game.");
        echo PHP_EOL;

        if ($frame % 30 === 0) {
            $this->generateBuilding();
        }

        $this->moveBuildings();

        for ($y = 0; $y < $this->gridDimensions['height']; $y++) {
            for ($x = 0; $x < $this->gridDimensions['width']; $x++) {
                $isFlappyManPosition = $x === $this->flappyManPosition['x'] && $y === $this->flappyManPosition['y'];
                $isTopOrBottomBorder = $y === 0 || $y === $this->gridDimensions['height'] - 1;
                $isRightBorder = $x === $this->gridDimensions['width'] - 1;

                // Width and height of the sun
                $sunWidth = count($this->sunPattern[0]);
                $sunHeight = count($this->sunPattern);
                $sunX = $this->gridDimensions['width'] - 8;
                $sunY = 2;
                // Check if we are at the sun position
                $isSunPosition = $x >= $sunX && $x < $sunX + $sunWidth && $y >= $sunY && $y < $sunY + $sunHeight && $this->sunPattern[$y - $sunY][$x - $sunX] === 1;

                // Vérifier si Flappy-man se trouve sur cette position
                if ($isFlappyManPosition) {
                    // Vérifie si Flappy-man est mort (a touché le bas de la grille)
                    if (preg_match('/[|V\[\]]/', $this->getBuildingChar(17, $this->flappyManPosition['y']))) {
                        echo "     {$this->colors['red']}<O|XX{$this->colors['reset']}";
                    } elseif ($this->flappyManPosition['y'] >= $this->gridDimensions['height'] - 2) {
                        echo $this->flappyManDead;
                    } else {
                        echo $this->flappyManAlive[$frame % 3];
                    }

                    $x += 9; // Skip the next 9 characters to avoid overlapping with Flappy-man
                } elseif($this->getBuildingChar($x, $y) !== null) {
                    if ($y === $this->flappyManPosition['y'] && $x <= 20  && (
                        preg_match('/[|V\[\]]/', $this->getBuildingChar(20, $this->flappyManPosition['y'])) ||
                        preg_match('/[|V\[\]]/', $this->getBuildingChar(19, $this->flappyManPosition['y'])) ||
                        preg_match('/[|V\[\]]/', $this->getBuildingChar(18, $this->flappyManPosition['y']))
                    )) {
                        echo "|| ";
                        $x += 2; // Skip the next character to avoid overlapping with the building
                    } elseif ($y === $this->flappyManPosition['y'] && $x < 15 && $x > 7) {
                        // Fix a bug where Flappy-man would overlap with the building
                        echo $this->flappyManAlive[$frame % 3];
                        $x += 9; // Skip the next 9 characters to avoid overlapping with the building
                    } else {
                        // Show the building
                        echo $this->getBuildingChar($x, $y);
                        $x += 2; // Skip the next character to avoid overlapping with the building
                    }

                }elseif ($isSunPosition) {
                    // Show the sun
                    echo "{$this->sunColor}*{$this->colors['reset']}";
                } elseif ($isTopOrBottomBorder) {
                    // Show the top and bottom border
                    echo '#';
                } elseif ($isRightBorder) {
                    // Show the right border
                    echo '||';
                } else {
                    // Otherwise, show empty space
                    echo ' ';
                }
            }
            echo PHP_EOL;
        }

        // Check if Floppy-man is at the bottom of the grid
        if (preg_match('/[|V\[\]]/', $this->getBuildingChar(17, $this->flappyManPosition['y'])) ||
        $this->flappyManPosition['y'] === $this->gridDimensions['height']-2) {
            $this->info("{$this->colors['red']}GAME OVER!{$this->colors['green']} Your score: " . $frame);
            $loop->stop();
        } else {
            $this->info('score : ' . $frame);
        }
        $this->flappyManPosition['y'] += $this->gravity;
    }

    private function detectInput(LoopInterface $loop): void
    {
        // Configure terminal to raw mode to capture key presses immediately
        system('stty cbreak -echo');

        $stdin = new ReadableResourceStream(STDIN, $loop);
        $stdin->on('data', function ($data) use ($loop) {
            $key = $data;

            if ($key === self::SPACE_KEY) {
                $maxQuantity = min(5, $this->flappyManPosition['y'] - 1);
                $this->flappyManPosition['y'] -= $maxQuantity;
            }

            if ($key === self::QUIT_KEY) {
                $this->info('Exiting game...');
                $loop->stop();
            }
        });
    }
}
