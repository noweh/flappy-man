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
            "{$this->colors['red']}__\"°{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}__{$this->colors['reset']}",
            "{$this->colors['red']}__`¯{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}__{$this->colors['reset']}",
            "{$this->colors['red']}__^\"{$this->colors['blue']},^{$this->colors['red']}>{$this->colors['white']}O{$this->colors['blue']}__{$this->colors['reset']}",
        ];

        $this->flappyManDead = "{$this->colors['red']}___n~\_O_/{$this->colors['reset']}";
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

    private function drawFrame(int $frame, $loop): void
    {
        echo PHP_EOL;
        $this->info('Aidez Flappy-Man à voler entre les batiments !');
        echo PHP_EOL;

        for ($y = 0; $y < $this->gridDimensions['height']; $y++) {
            for ($x = 0; $x < $this->gridDimensions['width']; $x++) {
                $isFlappyManPosition = $x === $this->flappyManPosition['x'] && $y === $this->flappyManPosition['y'];
                $isTopOrBottomBorder = $y === 0 || $y === $this->gridDimensions['height'] - 1;
                $isRightBorder = $x === $this->gridDimensions['width'] - 1;
                $isSunPosition = $x >= $this->gridDimensions['width'] - 8 && $y <= 5 && $y >= 2 && $x < $this->gridDimensions['width'] - 3;
                $isFlappyManRightBorder = $y === $this->flappyManPosition['y'] && $x === $this->gridDimensions['width'] - 10;
                $isFlappyManDead = $this->flappyManPosition['y'] === $this->gridDimensions['height'] - 2;
                $isFlappyManRow = $y === $this->flappyManPosition['y'];
                $isWithinGridWidth = $x + 9 < $this->gridDimensions['width'];

                if ($isFlappyManPosition) {
                    // Display the Flappy-man dead or alive depending on the position
                    echo $isFlappyManDead ? $this->flappyManDead : $this->flappyManAlive[$frame % 3];
                } elseif ($isFlappyManRow && $isWithinGridWidth || !$isFlappyManRow) {
                    if ($isSunPosition) {
                        // Display a sun in the sky
                        echo "{$this->colors['yellow']}#{$this->colors['reset']}";
                    } elseif ($isTopOrBottomBorder) {
                        // Display the top and bottom borders
                        echo '#';
                    } elseif ($isRightBorder || $isFlappyManRightBorder) {
                        // Display the right border and the right border of the flappy
                        echo '||';
                    } else {
                        echo ' ';
                    }
                }
            }
           echo PHP_EOL;
        }
        $this->flappyManPosition['y'] += 1;

        // Check if Floppy-man is at the bottom of the grid
        if ($this->flappyManPosition['y'] === $this->gridDimensions['height']-1) {
            $this->info('Game over! Your score: ' . $frame);
            $loop->stop();
        }
        $this->info('score : ' . $frame);
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
