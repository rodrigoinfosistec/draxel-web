<?php

namespace App\Modules\Worktime\Parsers;

use App\Modules\Worktime\Contracts\ClockDeviceParserInterface;
use Carbon\Carbon;
use InvalidArgumentException;

class KnupRE1032Parser implements ClockDeviceParserInterface
{
    public function validate(string $content): void
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));

        if (! is_array($lines) || count($lines) < 2) {
            throw new InvalidArgumentException('O arquivo está vazio ou inválido.');
        }

        $header = str_getcsv((string) $lines[0], "\t");

        if (
            ! in_array('EnNo', $header, true)
            || ! in_array('DateTime', $header, true)
        ) {
            throw new InvalidArgumentException('O arquivo não corresponde ao layout esperado do device.');
        }
    }

    public function parse(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));

        if (! is_array($lines) || count($lines) < 2) {
            return [];
        }

        $header = str_getcsv((string) array_shift($lines), "\t");

        return collect($lines)
            ->filter(fn ($line) => filled(trim((string) $line)))
            ->values()
            ->map(function (string $line, int $index) use ($header) {
                $columns = str_getcsv($line, "\t");

                if (count($columns) !== count($header)) {
                    return [
                        'line_number' => $index + 2,
                        'raw_line' => $line,
                        'employee_code' => null,
                        'recorded_at' => null,
                        'payload' => null,
                    ];
                }

                $row = array_combine($header, $columns);

                $employeeCode = trim((string) ($row['EnNo'] ?? ''));
                $dateTime = trim((string) ($row['DateTime'] ?? ''));

                $recordedAt = null;

                if ($dateTime !== '') {
                    try {
                        $recordedAt = Carbon::createFromFormat('Y-m-d H:i:s', $dateTime);
                    } catch (\Throwable $exception) {
                        $recordedAt = null;
                    }
                }

                return [
                    'line_number' => $index + 2,
                    'raw_line' => $line,
                    'employee_code' => $employeeCode !== '' ? $employeeCode : null,
                    'recorded_at' => $recordedAt,
                    'payload' => [
                        'name' => $row['Name'] ?? null,
                        'mode' => $row['Mode'] ?? null,
                        'in_out' => $row['In/Out'] ?? null,
                        'vm' => $row['VM'] ?? null,
                        'department' => $row['Department'] ?? null,
                    ],
                ];
            })
            ->all();
    }
}
