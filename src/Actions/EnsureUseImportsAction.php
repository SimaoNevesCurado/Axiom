<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

final readonly class EnsureUseImportsAction
{
    /**
     * @param  list<string>  $imports
     */
    public function handle(string $contents, array $imports): string
    {
        $missing = array_values(array_filter(
            $imports,
            static fn (string $import): bool => ! str_contains($contents, $import),
        ));

        if ($missing === []) {
            return $contents;
        }

        if (preg_match_all('/^use\s+[^;]+;[ \t]*$/m', $contents, $matches, PREG_OFFSET_CAPTURE) !== false && $matches[0] !== []) {
            $existing = array_map(
                static fn (array $match): string => trim($match[0]),
                $matches[0],
            );
            $allImports = array_values(array_unique([...$existing, ...$missing]));
            sort($allImports);

            $first = $matches[0][0];
            $last = $matches[0][array_key_last($matches[0])];
            $start = $first[1];
            $end = $last[1] + strlen($last[0]);

            return substr($contents, 0, $start)
                .implode("\n", $allImports)
                ."\n\n"
                .ltrim(substr($contents, $end), "\n");
        }

        sort($missing);

        if (preg_match('/^namespace\s+[^;]+;[ \t]*$/m', $contents, $namespace, PREG_OFFSET_CAPTURE) === 1) {
            $line = $namespace[0][0];
            $offset = $namespace[0][1] + strlen($line);

            return substr($contents, 0, $offset)."\n\n".implode("\n", $missing)."\n\n".ltrim(substr($contents, $offset), "\n");
        }

        if (preg_match('/^declare\s*\(strict_types=1\);[ \t]*$/m', $contents, $declare, PREG_OFFSET_CAPTURE) === 1) {
            $line = $declare[0][0];
            $offset = $declare[0][1] + strlen($line);

            return substr($contents, 0, $offset)."\n\n".implode("\n", $missing)."\n\n".ltrim(substr($contents, $offset), "\n");
        }

        if (str_starts_with($contents, '<?php')) {
            return "<?php\n\n".implode("\n", $missing)."\n\n".ltrim(substr($contents, 5), "\n");
        }

        return implode("\n", $missing)."\n\n".$contents;
    }
}
