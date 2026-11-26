<?php
/**
 * Gera o sitemap.xml do site na raiz do repositório.
 *
 * O site usa Docsify em modo history (URLs reais, sem hash), portanto cada
 * página da documentação tem a forma: https://l4zarin3.github.io/hyperf-doc-pt/3.2/<caminho>
 *
 * Uso: php tools/generate-sitemap.php
 */
$base = 'https://l4zarin3.github.io/hyperf-doc-pt';
$dir  = __DIR__ . '/../3.2';

$urls = [$base . '/', $base . '/3.2/'];

$skip = ['README.md', 'summary.md', 'memory.md', '_404.md'];

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'md') {
        continue;
    }
    if (in_array($file->getFilename(), $skip, true)) {
        continue;
    }
    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
    $urls[] = $base . '/3.2/' . substr($rel, 0, -3); // remove .md
}

$urls = array_values(array_unique($urls));
sort($urls);

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $url) {
    $xml .= "  <url><loc>" . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
}
$xml .= "</urlset>\n";

file_put_contents(__DIR__ . '/../sitemap.xml', $xml);

echo 'Sitemap gerado com ' . count($urls) . ' URLs.' . PHP_EOL;
