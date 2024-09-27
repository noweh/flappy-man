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
        'maroon' => "\033[35m",
        'yellow' => "\033[33m",
        'green' => "\033[32m",
        'reset' => "\033[0m",
    ];

    private array $earthArray = [];

    private array $kryptonArray = [];

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

    private string $selectedPlanet;
    private string $sunColor;

    private int $gravity;

    private string $buildingsColor;

    public function __construct()
    {
        parent::__construct();

        $this->earthArray = [
            "{$this->colors['blue']}        _____",
            "{$this->colors['blue']}    ,-:` \;',`'-,",
            "{$this->colors['blue']}  .'-;_,;  ':-;_,'.",
            "{$this->colors['blue']} /;   '/    ,  _`.-\ ",
            "{$this->colors['blue']}| '`. (`     /` ` \`|",
            "{$this->colors['blue']}|:.  `\`-.   \_   / |",
            "{$this->colors['blue']}|     (   `,  .`\ ;'|",
            "{$this->colors['blue']} \     | .'     `-'/",
            "{$this->colors['blue']}  `.   ;/        .'",
            "{$this->colors['blue']}    `'-._____."
        ];

        $this->kryptonArray = [
            "{$this->colors['red']}         ,MMM8&&&.",
            "{$this->colors['red']}    _...MMMMM88&&&&..._",
            "{$this->colors['red']} .::'''MMMMM88&&&&&&'''::.",
            "{$this->colors['red']}::     MMMMM88&&&&&&     ::",
            "{$this->colors['red']}'::....MMMMM88&&&&&&....::'",
            "{$this->colors['red']}   `''''MMMMM88&&&&''''`",
            "{$this->colors['red']}         'MMM8&&&'",
        ];

        $this->flappyManIntroductionArray = [
            "             {$this->colors['white']}.=.,{$this->colors['reset']}",
            "            {$this->colors['white']};c =\ {$this->colors['reset']}",
            "          {$this->colors['red']}__{$this->colors['white']}|  _/{$this->colors['reset']}",
            "        {$this->colors['blue']}.{$this->colors['red']}'{$this->colors['blue']}-{$this->colors['red']}'{$this->colors['blue']}-._{$this->colors['red']}/{$this->colors['blue']}-{$this->colors['red']}'{$this->colors['blue']}-._{$this->colors['reset']}",
            "       {$this->colors['blue']}/..   {$this->colors['red']}____    {$this->colors['blue']}\ {$this->colors['reset']}",
            "      {$this->colors['blue']}/' _  {$this->colors['red']}[ ---] {$this->colors['blue']})  \ {$this->colors['reset']}",
            "     {$this->colors['blue']}(  / \--{$this->colors['red']}\_|¯ {$this->colors['blue']}-/'. ){$this->colors['reset']}",
            "      {$this->colors['blue']}\-;_/}\__;__/ _/ _/{$this->colors['reset']}",
            "       {$this->colors['blue']}'.{$this->colors['white']}_}{$this->colors['blue']}|==o==\\{$this->colors['white']}{_{$this->colors['blue']}\/{$this->colors['reset']}"
        ];

        $this->flappyManAlive = [
            "{$this->colors['red']},_\"°{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']},{$this->colors['reset']}",
            "{$this->colors['red']},_`¯{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']},{$this->colors['reset']}",
            "{$this->colors['red']},_^\"{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}_{$this->colors['white']},{$this->colors['reset']}",
        ];

        $this->flappyManDead = "{$this->colors['red']}___n~\_O_/{$this->colors['reset']}";

        // Configure terminal to raw mode to capture key presses immediately
        system('stty cbreak -echo');

        $this->selectedPlanet = 'earth';
        $this->gravity = 1;
        $this->sunColor = $this->colors['yellow'];
        $this->buildingsColor = $this->colors['white'];
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $loop = Loop::get();

        $state = "introduction";

        $stdin = new ReadableResourceStream(STDIN, $loop);
        $stdin->on('data', function ($data) use (&$state, $loop) {
            $key = ord($data);

            switch ($state) {
                case 'introduction':
                    if ($key === 10) { // Enter key
                        $state = 'selectDifficulty';
                    }
                    break;
                case 'selectDifficulty':
                    if ($key === 27) { // Arrow keys
                        $this->selectedPlanet = ($this->selectedPlanet === 'earth') ? 'krypton' : 'earth';
                        if ($this->selectedPlanet === 'earth') {
                            $this->gravity = 1;
                            $this->sunColor = $this->colors['yellow'];
                            $this->buildingsColor = $this->colors['white'];
                        } else {
                            $this->gravity = 2;
                            $this->sunColor = $this->colors['red'];
                            $this->buildingsColor = $this->colors['blue'];
                        }
                    }
                    if ($key === 10) { // Enter key
                        $state = 'game';
                    }
                    break;
                case 'game':
                    if ($data === self::SPACE_KEY) {
                        $maxQuantity = min(5, $this->flappyManPosition['y'] - 1);
                        $this->flappyManPosition['y'] -= $maxQuantity;
                    }
                    break;
            }


            if ($data === self::QUIT_KEY) {
                $this->info('          Exiting game...');
                $loop->stop();
                // Restore terminal settings
                system('stty -cbreak echo');
            }
        });

        $frame = 0;
        $loop->addPeriodicTimer(0.1, function () use (&$state, &$frame, $loop) {
            switch ($state) {
                case 'introduction':
                    $this->drawIntroduction();
                    break;
                case 'selectDifficulty':
                    $this->drawSelectDifficulty();
                    break;
                case 'game':
                    $this->drawGame($frame, $loop);
                    $frame++;
                    break;
            }
        });

        $loop->run();
    }

    private function drawIntroduction(): void
    {
        $this->clearGrid();
        $this->info(PHP_EOL);
        foreach ($this->flappyManIntroductionArray as $flappyManIntroduction) {
            $this->info("          " . $flappyManIntroduction);
        }

        $this->info("               {$this->colors['white']}Welcome to Flappy-man!{$this->colors['reset']}");
        $this->info(PHP_EOL);
        $this->info(PHP_EOL);



        $this->info("          Press {$this->colors['white']}'ENTER'{$this->colors['green']} to start the game...");
    }

    public function drawSelectDifficulty(): void
    {
        $this->clearGrid();
        $this->info(PHP_EOL);
        $this->info("          Select a Planet with your {$this->colors['white']}'Arrow Keys'{$this->colors['reset']}");
        $this->info(PHP_EOL);
        $this->info("               {$this->colors['white']}Earth{$this->colors['reset']} or {$this->colors['white']}Krypton{$this->colors['reset']}");
        $this->info(PHP_EOL);
        if ($this->selectedPlanet === 'earth') {
            foreach ($this->earthArray as $earthLine) {
                $this->info("               " . $earthLine);
            }$this->info(PHP_EOL);
            $this->info("                       " . $this->colors['white'] . 'Earth' . $this->colors['reset']);
        } else {
            foreach ($this->kryptonArray as $kryptonLine) {
                $this->info("               " . $kryptonLine);
            }$this->info(PHP_EOL);
            $this->info("                         " . $this->colors['white'] . 'Krypton' . $this->colors['reset']);
        }
        $this->info(PHP_EOL);
        $this->info("          Press {$this->colors['white']}'ENTER'{$this->colors['green']} to start the game...");


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
                    $row .= $this->buildingsColor . $char[array_rand($char)] . $this->colors['reset'];
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
            // Check if x is in the building range (including thickness)
            if ($x >= $building->width && $x < $building->width + $building->thickness) {
                // If yes, return the character corresponding to the building row
                return $building->render[$y] ?? null;
            }
        }


        return null; // No building at this position
    }

    private function drawGame(int $frame, $loop): void
    {
        $this->clearGrid();
        echo PHP_EOL;
        $this->info("          {$this->colors['blue']}How to play:{$this->colors['green']} Press {$this->colors['white']}'SPACE' {$this->colors['green']} to make Flappy fly up, {$this->colors['white']}'Q'{$this->colors['green']} to quit the game.");
        echo PHP_EOL;

        if ($frame % 20 === 0) {
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
                    // Check if Flappy-man is dead (hit the bottom of the grid)
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
            // Restore terminal settings
            system('stty -cbreak echo');
        } else {
            $this->info('score : ' . $frame);
        }
        $this->flappyManPosition['y'] += $this->gravity;
    }
}
