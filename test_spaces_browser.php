<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\DigitalOceanSpacesService;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set up Laravel app configuration
$app = new Illuminate\Foundation\Application(__DIR__);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Set up basic config
config([
    'filesystems.disks.s3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'fra1'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ]
]);

echo "=== DigitalOcean Spaces Browser Test ===\n\n";

try {
    echo "Testing DigitalOcean Spaces Service...\n";
    echo "Bucket: " . env('AWS_BUCKET') . "\n";
    echo "Endpoint: " . env('AWS_ENDPOINT') . "\n";
    echo "Region: " . env('AWS_DEFAULT_REGION') . "\n\n";

    $spacesService = new DigitalOceanSpacesService();
    
    echo "Listing root directory contents...\n";
    $contents = $spacesService->listContents('', false);
    
    echo "Found " . count($contents) . " items:\n\n";
    
    foreach ($contents as $item) {
        $type = $item['type'] === 'directory' ? '[DIR]' : '[FILE]';
        $size = $item['size'] ? DigitalOceanSpacesService::formatFileSize($item['size']) : '-';
        $modified = $item['lastModified'] ? $item['lastModified']->format('Y-m-d H:i:s') : '-';
        
        echo sprintf("%-6s %-30s %-10s %s\n", 
            $type, 
            $item['name'], 
            $size, 
            $modified
        );
    }
    
    echo "\n=== Test completed successfully! ===\n";
    
    // Test breadcrumbs
    echo "\nTesting breadcrumbs for path 'documents/customers':\n";
    $breadcrumbs = DigitalOceanSpacesService::getBreadcrumbs('documents/customers');
    foreach ($breadcrumbs as $breadcrumb) {
        echo "- {$breadcrumb['name']} ({$breadcrumb['path']})\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
