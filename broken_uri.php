<?php

if (count($argv) !== 3) {
    $filename = __FILE__;
    echo "Usage: php {$filename} {input_csv} {pattern}\n";
    echo "Example: php {$filename} urls.csv .html\n";
    exit();
}

$inputCsv = $argv[1];
$pattern = $argv[2]; // Le pattern attendu à la fin des URLs, ex: ".html"

// Fonction utilitaire pour vérifier si une chaîne se termine par un pattern
function endsWith($string, $pattern) {
    return substr($string, -strlen($pattern)) === $pattern;
}

// Ouvrir le fichier CSV
$handle = fopen($inputCsv, 'r');
if (!$handle) {
    die("Error: Unable to open file {$inputCsv}\n");
}

$invalidUrls = [];
$loop = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    $loop++;
    if ($loop === 1) {
        // Si c'est la première ligne (titres), on passe
        continue;
    }

    // Supposons que les URLs sont dans la première colonne
    $url = $row[0];

    // Vérifier si l'URL ne se termine pas par le pattern
    if (!endsWith($url, $pattern)) {
        $invalidUrls[] = $url;
    }
}

fclose($handle);

// Écrire les résultats dans un fichier
$outputFile = "invalid_urls.csv";
$outputHandle = fopen($outputFile, 'w');
if (!$outputHandle) {
    die("Error: Unable to create output file {$outputFile}\n");
}

foreach ($invalidUrls as $url) {
    fputcsv($outputHandle, [$url], ';');
}
fclose($outputHandle);

// Résumé
echo "Total URLs checked: {$loop}\n";
echo "Invalid URLs found: " . count($invalidUrls) . "\n";
echo "Invalid URLs written to: {$outputFile}\n";

