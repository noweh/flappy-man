<?php

namespace App\Game;

class World
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly int $gravity,
        public readonly string $sunColor,
        public readonly string $buildingsColor,
        public readonly string $artColor,
        public readonly array $art,
    ) {}

    /**
     * All playable worlds, keyed by identifier. Add a new entry here to add a world.
     *
     * @return array<string, self>
     */
    public static function all(): array
    {
        return [
            'earth' => new self(
                key: 'earth',
                name: 'Earth',
                gravity: 1,
                sunColor: 'yellow',
                buildingsColor: 'white',
                artColor: 'blue',
                art: [
                    '        _____',
                    "    ,-:` \;',`'-,",
                    "  .'-;_,;  ':-;_,'.",
                    " /;   '/    ,  _`.-\ ",
                    "| '`. (`     /` ` \`|",
                    "|:.  `\`-.   \_   / |",
                    "|     (   `,  .`\ ;'|",
                    " \     | .'     `-'/",
                    "  `.   ;/        .'",
                    "    `'-._____.",
                ],
            ),
            'krypton' => new self(
                key: 'krypton',
                name: 'Krypton',
                gravity: 2,
                sunColor: 'red',
                buildingsColor: 'blue',
                artColor: 'red',
                art: [
                    '         ,MMM8&&&.',
                    '    _...MMMMM88&&&&..._',
                    " .::'''MMMMM88&&&&&&'''::.",
                    '::     MMMMM88&&&&&&     ::',
                    "'::....MMMMM88&&&&&&....::'",
                    "   `''''MMMMM88&&&&''''`",
                    "         'MMM8&&&'",
                ],
            ),
        ];
    }

    public static function after(string $key): self
    {
        $keys = array_keys(self::all());
        $index = array_search($key, $keys, true);

        return self::all()[$keys[($index + 1) % count($keys)]];
    }
}
