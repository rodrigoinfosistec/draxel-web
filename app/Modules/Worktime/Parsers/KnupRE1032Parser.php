<?php

namespace App\Modules\Worktime\Parsers;

use App\Modules\Worktime\Contracts\ClockDeviceParserInterface;
use Carbon\Carbon;
use InvalidArgumentException;

class KnupRE1032Parser implements ClockDeviceParserInterface
{
    public function validate(string $content): void
    {
        $lines = $this->extractLines($content);

        if (count($lines) < 2) {
            throw new InvalidArgumentException('O arquivo está vazio ou inválido.');
        }

        $header = $this->parseHeader($lines[0]);

        if (
            ! in_array('EnNo', $header, true)
            || ! in_array('DateTime', $header, true)
        ) {
            throw new InvalidArgumentException('O arquivo não corresponde ao layout esperado do device.');
        }
    }

    public function parse(string $content): array
    {
        $lines = $this->extractLines($content);

        if (count($lines) < 2) {
            return [];
        }

        $header = $this->parseHeader(array_shift($lines));

        return collect($lines)
            ->filter(fn (string $line) => filled(trim($line)))
            ->values()
            ->map(function (string $line, int $index) use ($header) {
                $columns = $this->parseColumns($line);

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

                $employeeCode = $this->normalizeValue($row['EnNo'] ?? null);
                $dateTime = $this->normalizeValue($row['DateTime'] ?? null);

                $recordedAt = null;

                if ($dateTime !== null) {
                    try {
                        $recordedAt = Carbon::createFromFormat('Y-m-d H:i:s', $dateTime);
                    } catch (\Throwable $exception) {
                        $recordedAt = null;
                    }
                }

                return [
                    'line_number' => $index + 2,
                    'raw_line' => $line,
                    'employee_code' => $employeeCode,
                    'recorded_at' => $recordedAt,
                    'payload' => [
                        'name' => $this->normalizeValue($row['Name'] ?? null),
                        'mode' => $this->normalizeValue($row['Mode'] ?? null),
                        'vm' => $this->normalizeValue($row['VM'] ?? null),
                        'department' => $this->normalizeValue($row['Department'] ?? null),
                    ],
                ];
            })
            ->all();
    }

    protected function extractLines(string $content): array
    {
        $content = $this->normalizeContent($content);

        $lines = preg_split("/\r\n|\n|\r/", $content);

        if (! is_array($lines)) {
            return [];
        }

        return collect($lines)
            ->map(fn (string $line) => $this->stripBom($line))
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();
    }

    protected function parseHeader(string $line): array
    {
        return collect($this->parseColumns($line))
            ->map(fn ($column) => $this->normalizeValue($column))
            ->filter(fn ($column) => $column !== null)
            ->values()
            ->all();
    }

    protected function parseColumns(string $line): array
    {
        return array_map(
            fn ($value) => is_string($value) ? trim($value) : $value,
            str_getcsv($line, "\t")
        );
    }

    protected function normalizeContent(string $content): string
    {
        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16BE');
        } else {
            $detectedEncoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252'], true);

            if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
            }
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        return trim($content);
    }

    protected function stripBom(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        $value = preg_replace('/^\x{FEFF}/u', '', $value) ?? $value;

        return trim($value);
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = $this->stripBom($value);

        return $value !== '' ? $value : null;
    }
}
