<?php

$compDir = realpath(__DIR__ . '/../../resources/views/components');
$viewsDir = realpath(__DIR__ . '/../../resources/views');
$appDir = realpath(__DIR__ . '/../../app');

$files = scandir($compDir);
$components = [];

foreach ($files as $f) {
    if (str_ends_with($f, '.blade.php')) {
        $components[] = substr($f, 0, -10); // remove .blade.php
    }
}

// Read all blade and php files
$allFiles = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($iter as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $allFiles[] = $file->getPathname();
    }
}
$iter2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));
foreach ($iter2 as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
        $allFiles[] = $file->getPathname();
    }
}

$results = [];

foreach ($components as $comp) {
    $xTag = "<x-$comp";
    $atComp = "@component('components.$comp'";
    $viewCall1 = "'components.$comp'";
    $viewCall2 = "\"components.$comp\"";
    
    $matches = [];
    foreach ($allFiles as $filePath) {
        // Skip the component file itself
        if ($filePath === realpath($compDir . '/' . $comp . '.blade.php')) continue;
        
        $content = file_get_contents($filePath);
        if (
            strpos($content, $xTag) !== false ||
            strpos($content, $atComp) !== false ||
            strpos($content, $viewCall1) !== false ||
            strpos($content, $viewCall2) !== false
        ) {
            $matches[] = str_replace(realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR, '', $filePath);
        }
    }
    
    $results[$comp] = [
        'is_referenced' => count($matches) > 0,
        'count' => count($matches),
        'references' => $matches,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
