<?php

declare(strict_types=1);

namespace App\Modules\Core\Console;

use Illuminate\Console\Command;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * Enforces the project's size rules: controllers stay under 150 lines and
 * design files stay small. Exits non-zero so it can gate a build.
 */
final class CheckFileLimitsCommand extends Command
{
    protected $signature = 'tanker:check-limits';

    protected $description = 'Report controllers over 150 lines and oversized view files';

    /**
     * @var array<string, array{path: string, pattern: string, limit: int}>
     */
    private const RULES = [
        'Controllers' => ['path' => 'app', 'pattern' => '*Controller.php', 'limit' => 150],
        'Views' => ['path' => 'resources/views', 'pattern' => '*.blade.php', 'limit' => 120],
        'Module views' => ['path' => 'app/Modules', 'pattern' => '*.blade.php', 'limit' => 120],
    ];

    public function handle(): int
    {
        $failed = false;

        foreach (self::RULES as $label => $rule) {
            $offenders = $this->offenders($rule['path'], $rule['pattern'], $rule['limit']);

            if ($offenders === []) {
                $this->components->info("{$label}: all within {$rule['limit']} lines.");

                continue;
            }

            $failed = true;
            $this->components->error("{$label}: over {$rule['limit']} lines.");
            $this->table(['File', 'Lines'], $offenders);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    private function offenders(string $path, string $pattern, int $limit): array
    {
        $directory = base_path($path);

        if (! is_dir($directory)) {
            return [];
        }

        $offenders = [];

        foreach (Finder::create()->files()->in($directory)->name($pattern) as $file) {
            $lines = $this->countLines($file);

            if ($lines > $limit) {
                $offenders[] = [$this->relative($file), $lines];
            }
        }

        usort($offenders, fn (array $a, array $b): int => $b[1] <=> $a[1]);

        return $offenders;
    }

    private function countLines(SplFileInfo $file): int
    {
        return count(file($file->getRealPath(), FILE_IGNORE_NEW_LINES));
    }

    private function relative(SplFileInfo $file): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getRealPath());
    }
}
