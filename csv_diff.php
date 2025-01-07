<?php
if (count($argv) !== 4) {
    $filename = __FILE__;
    echo "Run: {$filename} {existing_source_csv} {new_source_csv} {shared_unique_csv_cell_title}\n";
    echo "Example: {$filename} existing_data.csv current_production_data.csv ID\n";
    exit();
}

ini_set('auto_detect_line_endings', TRUE);

$existingTranslations = $argv[1];
$currentTranslationData = $argv[2];
$sharedUniqueId = $argv[3];

// Fonction utilitaire pour normaliser les valeurs
function normalize($value) {
    return strtolower(trim($value));
}

// Fonction pour extraire une URL sans le segment de catégorie
function normalizeUrl($url) {
    $parsedUrl = parse_url($url);
    if (!isset($parsedUrl['path'])) {
        return $url; // Si l'URL n'a pas de chemin, on la retourne telle quelle
    }
    $path = $parsedUrl['path'];
    // Supprimer la partie entre les deux premiers "/" dans le chemin
    return preg_replace('#^/[^/]+/#', '/', $path);
}

// Fonction pour obtenir l'index d'une colonne par son titre
function getIndexOf($value, $arrayData) {
    foreach ($arrayData as $index => $itemValue) {
        if (normalize($itemValue) === normalize($value)) {
            return $index;
        }
    }
    return null;
}

// Fonction pour afficher une ligne pour le débogage
function dumpRow($row) {
    echo "Content of the row:\n";
    foreach ($row as $index => $value) {
        echo "[{$index}] {$value}\n";
    }
}

// Lecture du fichier existant
$handle = fopen($existingTranslations, 'r');
if (!$handle) {
    die("Error opening file: {$existingTranslations}\n");
}

$uniqueIds = [];
$loop = 1;
$uniqueIdRowIndex = null;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if ($loop === 1) {
        $uniqueIdRowIndex = getIndexOf($sharedUniqueId, $row);
        if (is_null($uniqueIdRowIndex)) {
            dumpRow($row);
            throw new \LogicException("Cannot find unique header {$sharedUniqueId} in {$existingTranslations}");
        }
    } else {
        // Normaliser l'URL si c'est nécessaire
        $uniqueIds[] = normalizeUrl($row[$uniqueIdRowIndex]);
    }
    $loop++;
}
fclose($handle);

// Lecture du fichier actuel
$handle = fopen($currentTranslationData, 'r');
if (!$handle) {
    die("Error opening file: {$currentTranslationData}\n");
}

$loop = 1;
$uniqueIdRowIndex = null;
$diffedRows = [];
$simRow = [];
$cSkipped = 0;

while (($row = fgetcsv($handle, 0, ';')) !== false) {
    if ($loop === 1) {
        $uniqueIdRowIndex = getIndexOf($sharedUniqueId, $row);
        if (is_null($uniqueIdRowIndex)) {
            dumpRow($row);
            throw new \LogicException("Cannot find unique header {$sharedUniqueId} in {$currentTranslationData}");
        }
        $diffedRows[] = $row; // Ajouter les titres
    } else {
        // Normaliser l'URL avant la comparaison
        $id = normalizeUrl($row[$uniqueIdRowIndex]);
        var_dump($id);
        if (in_array($id, $uniqueIds)) {
            echo "Skipped (same unique id found): {$id}\n";
            $cSkipped++;
            $simRow[] = $row;
        } else {
            $diffedRows[] = $row;
        }
    }
    $loop++;
}
fclose($handle);

ini_set('auto_detect_line_endings', FALSE);

// Écriture des fichiers résultats
$filename = $currentTranslationData . ".diffed.csv";
$filenameSimilarity = $currentTranslationData . ".similarities.csv";

$handle = fopen($filename, 'w');
foreach ($diffedRows as $item) {
    fputcsv($handle, $item, ';');
}
fclose($handle);

$handleSim = fopen($filenameSimilarity, 'w');
foreach ($simRow as $item) {
    fputcsv($handleSim, $item, ';');
}
fclose($handleSim);

// Résumé
echo "Total rows skipped: {$cSkipped}\n";
echo "New rows in {$currentTranslationData}: " . count($diffedRows) . "\n";
echo "Diff written in: {$filename}\n";
echo "Similarities written in: {$filenameSimilarity}\n";
