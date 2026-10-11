<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/sbms.sql');

        if (! is_file($path)) {
            $this->command->error("Missing file: {$path}");
            $this->command->error('Copy your original sbms.sql into database/seeders/data/ and run the seeder again.');

            return;
        }

        $sql = file_get_contents($path);

        $this->load('service_categories', 'catID', $sql);
        $this->load('service', 'serviceID', $sql);
    }

    private function load(string $table, string $key, string $sql): void
    {
        if (DB::table($table)->exists()) {
            $this->command->info("{$table}: already has data, skipped.");

            return;
        }

        $rows = $this->parseInsert($sql, $table);

        // All-or-nothing: if any row fails, none are saved, so you can safely run the seeder again.
        DB::transaction(function () use ($table, $key, $rows) {
            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table($table)->insert($chunk);
            }

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', '{$key}'), (SELECT MAX(\"{$key}\") FROM \"{$table}\"))");
            }
        });

        $this->command->info(count($rows) . " rows loaded into {$table}.");
    }

    // Reads the rows of one "INSERT INTO `table` (...) VALUES (...), (...);" statement.
    private function parseInsert(string $sql, string $table): array
    {
        $pattern = '/INSERT INTO `' . preg_quote($table, '/') . '` \((.*?)\) VALUES\s*(.*?);\s*\n/s';

        if (! preg_match($pattern, $sql, $match)) {
            return [];
        }

        $columns = array_map(fn ($c) => trim($c, "` "), explode(',', $match[1]));
        $body = $match[2];
        $length = strlen($body);
        $rows = [];
        $i = 0;

        while ($i < $length) {
            if ($body[$i] !== '(') {
                $i++;

                continue;
            }

            $i++;
            $values = [];
            $current = '';
            $inQuote = false;
            $wasQuoted = false;

            for (; $i < $length; $i++) {
                $ch = $body[$i];

                if ($inQuote) {
                    if ($ch === '\\') {
                        $current .= $body[++$i];
                    } elseif ($ch === "'" && ($body[$i + 1] ?? '') === "'") {
                        $current .= "'";
                        $i++;
                    } elseif ($ch === "'") {
                        $inQuote = false;
                    } else {
                        $current .= $ch;
                    }

                    continue;
                }

                if ($ch === "'") {
                    $inQuote = true;
                    $wasQuoted = true;
                    $current = '';
                } elseif ($ch === ',' || $ch === ')') {
                    $value = $wasQuoted ? $current : trim($current);
                    $values[] = (! $wasQuoted && strtoupper($value) === 'NULL') ? null : $value;
                    $current = '';
                    $wasQuoted = false;

                    if ($ch === ')') {
                        $i++;
                        break;
                    }
                } else {
                    $current .= $ch;
                }
            }

            $rows[] = array_combine($columns, $values);
        }

        return $rows;
    }
}
