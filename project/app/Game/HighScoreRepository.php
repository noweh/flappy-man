<?php

namespace App\Game;

class HighScoreRepository
{
    private const MAX_ENTRIES_PER_WORLD = 10;

    public function __construct(private readonly string $path) {}

    /**
     * Scores sorted from best to worst, optionally filtered by world.
     *
     * @return array<int, array{initials: string, score: int, world: string}>
     */
    public function top(?string $world = null): array
    {
        $entries = $this->all();

        if ($world === null) {
            return $entries;
        }

        return array_values(array_filter(
            $entries,
            fn (array $entry): bool => $entry['world'] === $world,
        ));
    }

    public function bestScore(string $world): ?int
    {
        return $this->top($world)[0]['score'] ?? null;
    }

    public function qualifies(int $score, string $world): bool
    {
        $entries = $this->top($world);

        if (count($entries) < self::MAX_ENTRIES_PER_WORLD) {
            return true;
        }

        return $score > min(array_column($entries, 'score'));
    }

    public function add(string $initials, int $score, string $world): void
    {
        $entries = $this->all();
        $entries[] = [
            'initials' => strtoupper(substr($initials, 0, 3)),
            'score' => $score,
            'world' => $world,
        ];

        usort($entries, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        // Each world keeps its own top 10
        $countPerWorld = [];
        $entries = array_values(array_filter($entries, function (array $entry) use (&$countPerWorld): bool {
            $countPerWorld[$entry['world']] = ($countPerWorld[$entry['world']] ?? 0) + 1;

            return $countPerWorld[$entry['world']] <= self::MAX_ENTRIES_PER_WORLD;
        }));

        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0755, true);
        }

        file_put_contents($this->path, json_encode($entries, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<int, array{initials: string, score: int, world: string}>
     */
    private function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $entries = json_decode((string) file_get_contents($this->path), true);

        return is_array($entries) ? $entries : [];
    }
}
