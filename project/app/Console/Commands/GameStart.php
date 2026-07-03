<?php

namespace App\Console\Commands;

use App\Game\Engine;
use App\Game\HighScoreRepository;
use App\Game\Renderer;
use App\Game\World;
use Illuminate\Console\Command;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;

class GameStart extends Command
{
    protected $signature = 'game:start';

    protected $description = 'Start the game';

    private const SPACE_KEY = ' ';

    private const QUIT_KEY = 'q';

    private const PAUSE_KEY = 'p';

    private const RESTART_KEY = 'r';

    private const SCORES_KEY = 's';

    private const ENTER = 10;

    private const ESCAPE = 27;

    private const BACKSPACE = [8, 127];

    // ~1s showing the crash frame before switching to the game-over screen
    private const DEATH_FREEZE_TICKS = 10;

    // Smallest grid the game stays playable on; menus need ~60 columns too
    private const MIN_GRID_WIDTH = 60;

    private const MIN_GRID_HEIGHT = 15;

    // Lines used around the grid: 3 above (blank + help + blank), 1 status below,
    // plus 1 spare so the frame never scrolls
    private const CHROME_LINES = 5;

    private Renderer $renderer;

    private HighScoreRepository $highScores;

    private string $state = 'introduction';

    private World $world;

    private ?Engine $engine = null;

    private bool $paused = false;

    private int $deathTicks = 0;

    private bool $qualifies = false;

    private string $initials = '';

    private int $gridWidth = Engine::WIDTH;

    private int $gridHeight = Engine::HEIGHT;

    private ?int $best = null;

    // Bytes left to swallow from an ANSI escape sequence (arrow keys are ESC + 2 bytes)
    private int $escapeRemaining = 0;

    public function handle(): int
    {
        $this->renderer = new Renderer;
        $this->highScores = new HighScoreRepository(storage_path('app/highscores.json'));
        $this->world = World::all()['earth'];

        if (! $this->fitGridToTerminal()) {
            return self::FAILURE;
        }

        // Configure terminal to raw mode to capture key presses immediately
        system('stty cbreak -echo');
        echo "\033[?25l"; // Hide the cursor while the game is running

        $loop = Loop::get();

        // Restore the terminal even if the process dies on a fatal error
        register_shutdown_function(fn () => $this->restoreTerminal());

        // Restore the terminal on Ctrl+C / kill (signal handling requires ext-pcntl)
        if (extension_loaded('pcntl')) {
            foreach ([SIGINT, SIGTERM] as $signal) {
                $loop->addSignal($signal, function () use ($loop) {
                    $loop->stop();
                    $this->restoreTerminal();
                });
            }
        }

        $stdin = new ReadableResourceStream(STDIN, $loop);
        $stdin->on('data', function ($data) use ($loop) {
            // Process byte by byte: several key presses can arrive in a single chunk
            foreach (str_split($data) as $char) {
                $this->handleKeyPress($char, $loop);
            }
        });

        $loop->addPeriodicTimer(0.1, fn () => $this->onTick());

        $loop->run();

        return self::SUCCESS;
    }

    /**
     * Shrink the grid to the terminal when it is smaller than the default 100x25.
     * Without a TTY (piped input in tests) the defaults are kept.
     */
    private function fitGridToTerminal(): bool
    {
        $size = trim((string) shell_exec('stty size 2>/dev/null'));

        if (! preg_match('/^(\d+) (\d+)$/', $size, $matches)) {
            return true;
        }

        [, $rows, $cols] = array_map('intval', $matches);
        $this->gridWidth = min(Engine::WIDTH, $cols - 1);
        $this->gridHeight = min(Engine::HEIGHT, $rows - self::CHROME_LINES);

        if ($this->gridWidth < self::MIN_GRID_WIDTH || $this->gridHeight < self::MIN_GRID_HEIGHT) {
            $this->error(sprintf(
                'Terminal too small (%dx%d): Flappy-man needs at least %d columns and %d rows.',
                $cols,
                $rows,
                self::MIN_GRID_WIDTH + 1,
                self::MIN_GRID_HEIGHT + self::CHROME_LINES,
            ));

            return false;
        }

        return true;
    }

    private function onTick(): void
    {
        switch ($this->state) {
            case 'introduction':
                $this->renderer->draw($this->renderer->introduction());
                break;
            case 'selectWorld':
                $this->renderer->draw($this->renderer->worldSelection($this->world));
                break;
            case 'game':
                if (! $this->paused) {
                    $scoreBefore = $this->engine->score();
                    $this->engine->tick();
                    if ($this->engine->score() > $scoreBefore) {
                        echo "\x07"; // Terminal bell on each building passed ("\a" is not a PHP escape)
                    }
                }
                $this->renderer->draw($this->renderer->game($this->engine, $this->paused, $this->best));
                if ($this->engine->isOver()) {
                    echo "\x07";
                    $this->state = 'dying';
                    $this->deathTicks = self::DEATH_FREEZE_TICKS;
                }
                break;
            case 'dying':
                $this->renderer->draw($this->renderer->game($this->engine));
                if (--$this->deathTicks <= 0) {
                    $this->qualifies = $this->highScores->qualifies($this->engine->score(), $this->engine->world()->name);
                    $this->initials = '';
                    $this->state = 'gameOver';
                }
                break;
            case 'gameOver':
                $this->renderer->draw($this->renderer->gameOver($this->engine, $this->qualifies, $this->initials));
                break;
            case 'scoreboard':
                $this->renderer->draw($this->renderer->scoreboard($this->world, $this->highScores->top($this->world->name)));
                break;
        }
    }

    private function handleKeyPress(string $char, LoopInterface $loop): void
    {
        $key = ord($char);

        if ($this->escapeRemaining > 0) {
            $this->escapeRemaining--;

            return;
        }

        if ($key === self::ESCAPE) {
            $this->escapeRemaining = 2;
            if (in_array($this->state, ['selectWorld', 'scoreboard'], true)) {
                $this->world = World::after($this->world->key);
            }

            return;
        }

        // Initials entry captures letters, so the global quit key does not apply there
        if ($this->state === 'gameOver' && $this->qualifies) {
            $this->handleInitialsKey($char, $key);

            return;
        }

        $lower = strtolower($char);

        if ($lower === self::QUIT_KEY) {
            $this->quit($loop);

            return;
        }

        switch ($this->state) {
            case 'introduction':
                if ($key === self::ENTER) {
                    $this->state = 'selectWorld';
                } elseif ($lower === self::SCORES_KEY) {
                    $this->state = 'scoreboard';
                }
                break;
            case 'selectWorld':
                if ($key === self::ENTER) {
                    $this->startGame();
                }
                break;
            case 'game':
                if ($char === self::SPACE_KEY && ! $this->paused) {
                    $this->engine->flap();
                } elseif ($lower === self::PAUSE_KEY) {
                    $this->paused = ! $this->paused;
                } elseif ($lower === self::RESTART_KEY) {
                    $this->startGame();
                }
                break;
            case 'gameOver':
                if ($lower === self::RESTART_KEY) {
                    $this->startGame();
                } elseif ($lower === self::SCORES_KEY) {
                    $this->state = 'scoreboard';
                }
                break;
            case 'scoreboard':
                if ($lower === self::RESTART_KEY) {
                    $this->startGame();
                } elseif ($key === self::ENTER) {
                    $this->state = 'introduction';
                }
                break;
        }
    }

    private function handleInitialsKey(string $char, int $key): void
    {
        if ($key === self::ENTER) {
            if ($this->initials !== '') {
                $this->highScores->add($this->initials, $this->engine->score(), $this->engine->world()->name);
                $this->state = 'scoreboard';
            }
            // Empty ENTER skips saving and falls back to the restart/scores/quit menu
            $this->qualifies = false;

            return;
        }

        if (in_array($key, self::BACKSPACE, true)) {
            $this->initials = substr($this->initials, 0, -1);

            return;
        }

        if (strlen($this->initials) < 3 && preg_match('/[a-zA-Z0-9]/', $char)) {
            $this->initials .= strtoupper($char);
        }
    }

    private function startGame(): void
    {
        $this->engine = new Engine($this->world, null, $this->gridWidth, $this->gridHeight);
        $this->best = $this->highScores->bestScore($this->world->name);
        $this->paused = false;
        $this->state = 'game';
    }

    private function quit(LoopInterface $loop): void
    {
        $loop->stop();
        $this->restoreTerminal();
        $this->info('          Exiting game...');
    }

    private function restoreTerminal(): void
    {
        echo "\033[?25h"; // Show the cursor again
        system('stty -cbreak echo');
    }
}
