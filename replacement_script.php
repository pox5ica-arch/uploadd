<?php
/**
 * Theme Rebranding Script
 * This script will systematically replace all Shoptimizer references with Poxica Theme
 */

$root_dir = '/workspace/poxica-theme';

// Define replacement patterns
$replacements = [
    // Function prefixes
    'shoptimizer_' => 'poxica_theme_',
    'Shoptimizer_' => 'Poxica_Theme_',
    'SHOPTIMIZER_' => 'POXICA_THEME_',
    
    // Class names
    'class-shoptimizer-' => 'class-poxica-theme-',
    'Class_Shoptimizer_' => 'Class_Poxica_Theme_',
    
    // Text domain
    "'shoptimizer'" => "'poxica-theme'",
    '"shoptimizer"' => '"poxica-theme"',
    
    // Handle names and slugs
    'shoptimizer' => 'poxica-theme',
    'Shoptimizer' => 'Poxica Theme',
    
    // CSS classes and IDs (to preserve functionality)
    'poxica-theme-' => 'poxica-theme-',  // Keep this format for CSS
];

// File extensions to process
$extensions = ['php', 'css', 'js', 'json'];

function replaceInFile($filepath, $replacements) {
    if (!is_readable($filepath) || !is_writable($filepath)) {
        echo "Skipping $filepath - permission denied\n";
        return false;
    }
    
    $content = file_get_contents($filepath);
    if ($content === false) {
        echo "Failed to read $filepath\n";
        return false;
    }
    
    $original_content = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    if ($content !== $original_content) {
        if (file_put_contents($filepath, $content) !== false) {
            echo "Updated: $filepath\n";
            return true;
        } else {
            echo "Failed to write $filepath\n";
            return false;
        }
    }
    
    return true;
}

function processDirectory($dir, $replacements, $extensions) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (in_array($extension, $extensions)) {
                replaceInFile($file->getPathname(), $replacements);
            }
        }
    }
}

echo "Starting theme rebranding process...\n";
echo "Processing directory: $root_dir\n";

if (is_dir($root_dir)) {
    processDirectory($root_dir, $replacements, $extensions);
    echo "Rebranding process completed!\n";
} else {
    echo "Error: Directory $root_dir not found!\n";
}
?>