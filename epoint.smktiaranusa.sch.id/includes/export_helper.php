<?php
/**
 * HELPER EKSPOR DATA KE EXCEL (.xlsx) & CSV
 * Menghasilkan file Excel resmi OpenXML (.xlsx) dan CSV secara mandiri tanpa dependensi composer.
 */

if (!function_exists('rrmdir_helper')) {
    function rrmdir_helper($dir) {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item !== '.' && $item !== '..') {
                    $p = $dir . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($p) && !is_link($p)) rrmdir_helper($p);
                    else @unlink($p);
                }
            }
            @rmdir($dir);
        }
    }
}

if (!function_exists('downloadXlsx')) {
    function downloadXlsx($filename, $sheetTitle, $headers, $rows) {
        $tempDir = __DIR__ . '/../xlsx_exp_' . time() . '_' . mt_rand(1000, 9999);
        @mkdir($tempDir, 0755, true);
        @mkdir($tempDir . '/_rels', 0755, true);
        @mkdir($tempDir . '/xl', 0755, true);
        @mkdir($tempDir . '/xl/_rels', 0755, true);
        @mkdir($tempDir . '/xl/worksheets', 0755, true);

        // Shared strings
        $strings = [];
        $strMap = [];
        $getStrIdx = function($str) use (&$strings, &$strMap) {
            $s = (string)($str ?? '');
            if (!isset($strMap[$s])) {
                $strMap[$s] = count($strings);
                $strings[] = $s;
            }
            return $strMap[$s];
        };

        // Letter converter A..Z, AA..ZZ
        $getColLetter = function($idx) {
            $letter = '';
            while ($idx >= 0) {
                $letter = chr($idx % 26 + 65) . $letter;
                $idx = floor($idx / 26) - 1;
            }
            return $letter;
        };

        // Sheet XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $sheetXml .= '<cols>' . "\n";
        foreach ($headers as $i => $h) {
            $w = max(strlen($h) + 5, 12);
            if ($i === 0) $w = 8;
            elseif (stripos($h, 'nama') !== false) $w = 32;
            elseif (stripos($h, 'nis') !== false) $w = 16;
            $sheetXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>' . "\n";
        }
        $sheetXml .= '</cols>' . "\n";
        $sheetXml .= '<sheetData>' . "\n";

        // Row 1: Header
        $sheetXml .= '<row r="1" ht="26" customHeight="1">' . "\n";
        foreach ($headers as $i => $h) {
            $col = $getColLetter($i);
            $sIdx = $getStrIdx($h);
            $sheetXml .= '<c r="' . $col . '1" t="s" s="1"><v>' . $sIdx . '</v></c>';
        }
        $sheetXml .= '</row>' . "\n";

        // Data rows
        foreach ($rows as $rIdx => $row) {
            $rowNum = $rIdx + 2;
            $style = ($rIdx % 2 === 0) ? '2' : '3';
            $sheetXml .= '<row r="' . $rowNum . '" ht="20" customHeight="1">' . "\n";
            $cIdx = 0;
            foreach ($row as $val) {
                $col = $getColLetter($cIdx);
                $sIdx = $getStrIdx($val);
                $sheetXml .= '<c r="' . $col . $rowNum . '" t="s" s="' . $style . '"><v>' . $sIdx . '</v></c>';
                $cIdx++;
            }
            $sheetXml .= '</row>' . "\n";
        }

        $sheetXml .= '</sheetData>' . "\n";
        $sheetXml .= '</worksheet>';
        file_put_contents($tempDir . '/xl/worksheets/sheet1.xml', $sheetXml);

        // Styles XML
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="10"/><name val="Segoe UI"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0D9488"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="4">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="center"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="center"/>
    </xf>
  </cellXfs>
</styleSheet>';
        file_put_contents($tempDir . '/xl/styles.xml', $stylesXml);

        // Shared strings XML
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">' . "\n";
        foreach ($strings as $s) {
            $escaped = htmlspecialchars((string)$s, ENT_XML1, 'UTF-8');
            $ssXml .= '<si><t>' . $escaped . '</t></si>' . "\n";
        }
        $ssXml .= '</sst>';
        file_put_contents($tempDir . '/xl/sharedStrings.xml', $ssXml);

        // Content Types
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
        file_put_contents($tempDir . '/[Content_Types].xml', $contentTypes);

        // Root rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        file_put_contents($tempDir . '/_rels/.rels', $rootRels);

        // Workbook rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
        file_put_contents($tempDir . '/xl/_rels/workbook.xml.rels', $wbRels);

        // Workbook XML
        $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . htmlspecialchars($sheetTitle, ENT_XML1, 'UTF-8') . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
        file_put_contents($tempDir . '/xl/workbook.xml', $wbXml);

        // Compress
        $zipOut = $tempDir . '/output.zip';
        $zipped = false;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipOut, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if (!$file->isDir() && $file->getRealPath() !== realpath($zipOut)) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($tempDir) + 1);
                        $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
                    }
                }
                $zip->close();
                $zipped = true;
            }
        }

        if (!$zipped) {
            @exec('tar -c --format zip -f ' . escapeshellarg($zipOut) . ' -C ' . escapeshellarg($tempDir) . ' .');
            if (file_exists($zipOut) && filesize($zipOut) > 0) {
                $zipped = true;
            }
        }

        if ($zipped && file_exists($zipOut)) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
            header('Content-Length: ' . filesize($zipOut));
            header('Cache-Control: max-age=0');
            readfile($zipOut);
            rrmdir_helper($tempDir);
            exit;
        } else {
            rrmdir_helper($tempDir);
            // Fallback to CSV if zip failed
            downloadCsv($filename, $headers, $rows);
        }
    }
}

if (!function_exists('downloadCsv')) {
    function downloadCsv($filename, $headers, $rows) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $out = fopen('php://output', 'w');
        // BOM UTF-8 agar karakter rapi di Excel
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
