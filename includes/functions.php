<?php
function load_feedback(string $file): array {
    if (!is_file($file) || !is_readable($file)) return [];
    $handle = fopen($file, 'r');
    if (!$handle) return [];
    $headers = fgetcsv($handle);
    if (!$headers) { fclose($handle); return []; }
    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if (count($values) !== count($headers)) continue;
        $row = array_combine($headers, $values);
        if (!$row) continue;
        $row['sentiment'] = ucfirst(strtolower(trim($row['sentiment'] ?? 'Neutral')));
        if (!in_array($row['sentiment'], ['Positive','Negative','Neutral'], true)) $row['sentiment'] = 'Neutral';
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}
