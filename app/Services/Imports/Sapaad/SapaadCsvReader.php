<?php

namespace App\Services\Imports\Sapaad;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SapaadCsvReader
{
    public function read(string $path): array
    {
        $fullPath = Storage::path($path);

        if (! file_exists($fullPath)) {
            throw new RuntimeException(
                "CSV file not found: {$path}"
            );
        }

        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            throw new RuntimeException(
                "Unable to open CSV file: {$path}"
            );
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new RuntimeException(
                'CSV file is empty.'
            );
        }

        $headers = array_map(
            fn ($header) => $this->normalizeHeader($header),
            $headers
        );

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $row = array_pad(
                $row,
                count($headers),
                null
            );

            $rows[] = array_combine(
                $headers,
                array_slice($row, 0, count($headers))
            );
        }

        fclose($handle);

        return $rows;
    }

    protected function normalizeHeader(?string $header): string
    {
        $header = trim(
            preg_replace('/^\xEF\xBB\xBF/', '', $header ?? '')
        );

        return $header;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (
                $value !== null &&
                trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }
}