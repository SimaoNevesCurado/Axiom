<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class GeneratorInput
{
    /**
     * @param  array<int, mixed>  $definitions
     * @return list<array{name: string, type: string}>
     */
    public static function properties(array $definitions): array
    {
        $properties = [];

        foreach ($definitions as $definition) {
            if (! is_string($definition) || trim($definition) === '') {
                continue;
            }

            $parts = explode(':', $definition, 2);

            if (count($parts) !== 2) {
                throw new InvalidArgumentException("Invalid property definition [{$definition}]. Use name:type.");
            }

            [$name, $type] = array_map(static fn (string $value): string => trim($value), $parts);

            if ($name === '' || $type === '') {
                throw new InvalidArgumentException("Invalid property definition [{$definition}]. Use name:type.");
            }

            $properties[] = [
                'name' => Str::camel($name),
                'type' => $type,
            ];
        }

        return $properties;
    }
}
