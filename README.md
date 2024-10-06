# Flappy-man

![PHP](https://img.shields.io/badge/PHP-v8.2+-828cb7.svg?style=flat-square&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-v11.10+-f55247.svg?style=flat-square&logo=laravel)
![ReactPHP](https://img.shields.io/badge/ReactPHP-v1.5+-00d88e.svg?style=flat-square&logo=reactphp)
[![MIT Licensed](https://img.shields.io/github/license/noweh/livewire-memory-game)](licence)

Flappy-man is a fun and challenging game inspired by the classic *Flappy Bird*, but with a twist: it’s entirely built in PHP and designed to run in a Linux command-line terminal using ASCII characters.

![Cover Image](/assets/flappy-man.jpg)

## Table of Contents

- [Features](#features)
- [Demo](#demo)
- [Worlds](#worlds)
- [Controls](#controls)
- [Requirements](#requirements)
- [Installation](#installation)
- [License](#license)

## Features

- **Character Animation**: The main character, "Flappy-man", is animated with just ASCII characters, providing a unique visual experience.
- **Responsive Controls**: Control Flappy-man with the keyboard and help him fly through the air while avoiding obstacles.
- **Gravity Simulation**: Flappy-man gradually falls due to gravity, so press **SPACE** to keep him in the air.
- **Randomized Obstacles**: Each game generates a new set of buildings with randomly placed gaps, ensuring a fresh experience every time.
- **Collision Detection**: Impacting buildings or the ground ends the game, with a fun twist where the character display reacts to collisions.
- **ReactPHP-powered Animation**: Smooth scrolling of the obstacles and animation of Flappy-man is handled via ReactPHP for a seamless gameplay experience.

## Demo

Check out some GIFs of Flappy-man in action:

| Feature                | GIF Preview                                              |
|------------------------|----------------------------------------------------------|
| **Character Animation** | ![Character Animation](/assets/animation-flappy-man.gif) 
| **World Selection**    | ![World Selection](/assets/world-selection.gif)          |
| **Collision Detection** | ![Collision Detection](/assets/colision.gif)             |

## Worlds

| Worlds      | GIF Preview                                            |
|-------------|--------------------------------------------------------|
| **Earth**   | ![Obstacles and Gameplay](/assets/flappy-man-run.gif)  |
| **Krypton** | ![Obstacles and Gameplay](/assets/flappy-man-run2.gif) |

## Controls

- Use the **SPACE** key to make Flappy-man fly.
- Press **Q** to quit the game at any time.
- Avoid obstacles and try to get the highest score!

## Requirements

- PHP 8.0+
- Linux or macOS (Windows is not fully supported due to terminal limitations)
- Terminal with UTF-8 support

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/flappy-man.git
   cd flappy-man
    ```
   
2. Install the dependencies:
   ```bash
   composer install
   ```

3. Start the game:
   ```bash
   php artisan game:start
    ```

> **Note**: The game runs best on Linux-based systems. Windows support is limited due to how input handling is managed in terminals.
### Cache

To clear the cache, run the following command:

```bash
sh scripts/refresh_cache.sh
```

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.