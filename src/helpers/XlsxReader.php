<?php
declare(strict_types=1);

class XlsxReader
{
    /** @return list<string> */
    public static function extractNames(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Arquivo não encontrado.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException('Não foi possível abrir o arquivo Excel.');
        }

        $strings = [];
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared !== false) {
            $strings = self::parseSharedStrings($shared);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Planilha não encontrada no arquivo.');
        }

        $table = self::parseSheet($sheetXml, $strings);
        return self::namesFromTable($table);
    }

    private static function parseSharedStrings(string $xml): array
    {
        $out = [];
        $doc = new DOMDocument();
        @$doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($xpath->query('//m:si') as $si) {
            $out[] = trim($si->textContent);
        }
        return $out;
    }

    private static function parseSheet(string $xml, array $strings): array
    {
        $doc = new DOMDocument();
        @$doc->loadXML($xml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($xpath->query('//m:c') as $cell) {
            if (!$cell instanceof DOMElement) {
                continue;
            }
            $ref = $cell->getAttribute('r');
            if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                continue;
            }
            $col = self::columnIndex($m[1]);
            $row = (int) $m[2] - 1;
            $type = $cell->getAttribute('t');
            $vNodes = $xpath->query('m:v', $cell);
            $val = '';
            if ($vNodes->length > 0) {
                $val = $vNodes->item(0)->textContent;
                if ($type === 's') {
                    $val = $strings[(int) $val] ?? '';
                }
            }
            $rows[$row][$col] = trim($val);
        }

        ksort($rows);
        $table = [];
        foreach ($rows as $cols) {
            if ($cols === []) {
                continue;
            }
            ksort($cols);
            $table[] = array_values($cols);
        }
        return $table;
    }

    private static function columnIndex(string $letters): int
    {
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    /** @param list<list<string>> $table */
    private static function namesFromTable(array $table): array
    {
        $skip = ['nome', 'data', 'assinatura', 'lista do almoço', 'lista do almoco'];
        $names = [];
        $seen = [];

        foreach ($table as $row) {
            if (count($row) < 2) {
                continue;
            }
            $name = trim($row[1] ?? '');
            if ($name === '') {
                continue;
            }
            $lower = function_exists('mb_strtolower')
                ? mb_strtolower($name, 'UTF-8')
                : strtolower($name);
            if (in_array($lower, $skip, true)) {
                continue;
            }
            if (preg_match('/^\d+([.,]\d+)?$/', $name)) {
                continue;
            }
            $key = function_exists('mb_strtoupper')
                ? mb_strtoupper($name, 'UTF-8')
                : strtoupper($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }

        return $names;
    }
}
