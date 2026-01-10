<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project Storage Path
    |--------------------------------------------------------------------------
    |
    | The base path where project repositories and knowledge files are stored.
    |
    */

    'storage_path' => storage_path('app/projects'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Directories
    |--------------------------------------------------------------------------
    |
    | Directories that should be excluded from scanning by default.
    | These are matched exactly against directory names in the path.
    |
    */

    'excluded_directories' => [
        '.git',
        'node_modules',
        '.idea',
        '.vscode',
        '__pycache__',
        '.pytest_cache',
        '.mypy_cache',
        '.next',
        '.nuxt',
        '.output',
        'coverage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Include Vendor Directory
    |--------------------------------------------------------------------------
    |
    | Whether to include the vendor directory in scanning. Generally disabled
    | to reduce noise, but can be enabled for dependency analysis.
    |
    */

    'include_vendor' => false,

    /*
    |--------------------------------------------------------------------------
    | Include Storage Directory
    |--------------------------------------------------------------------------
    |
    | Whether to include the storage directory in scanning.
    |
    */

    'include_storage' => false,

    /*
    |--------------------------------------------------------------------------
    | Include Build Output Directories
    |--------------------------------------------------------------------------
    |
    | Whether to include build output directories (dist, build, etc).
    |
    */

    'include_build_output' => false,

    /*
    |--------------------------------------------------------------------------
    | Excluded File Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that should be excluded from content scanning.
    |
    */

    'excluded_extensions' => [
        'lock',
        'log',
        'map',
        'min.js',
        'min.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Binary Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions that are considered binary and won't have content scanned.
    |
    */

    'binary_extensions' => [
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'ico', 'webp', 'svg', 'avif',
        'mp3', 'mp4', 'wav', 'avi', 'mov', 'mkv', 'webm', 'ogg',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'tar', 'gz', 'rar', '7z', 'bz2',
        'exe', 'dll', 'so', 'dylib', 'bin',
        'ttf', 'otf', 'woff', 'woff2', 'eot',
        'sqlite', 'db', 'sqlite3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size for Content Scanning
    |--------------------------------------------------------------------------
    |
    | Files larger than this size (in bytes) will have metadata stored but
    | content will not be scanned or chunked. Default: 1MB
    |
    */

    'max_file_size' => 1024 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | Maximum size in bytes for each knowledge chunk. Default: 200KB
    |
    */

    'chunk_max_bytes' => 200 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Chunk Lines
    |--------------------------------------------------------------------------
    |
    | Maximum number of lines per chunk for large files.
    |
    */

    'chunk_max_lines' => 500,

    /*
    |--------------------------------------------------------------------------
    | GitHub Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret key for validating GitHub webhook signatures.
    |
    */

    'github_webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Pipeline Stages
    |--------------------------------------------------------------------------
    |
    | The stages of the scanning pipeline with their display names and weights
    | for progress calculation.
    |
    */

    'pipeline_stages' => [
        'workspace' => ['name' => 'Preparing workspace', 'weight' => 5],
        'clone' => ['name' => 'Cloning repository', 'weight' => 15],
        'manifest' => ['name' => 'Building file manifest', 'weight' => 30],
        'stack' => ['name' => 'Detecting stack', 'weight' => 10],
        'chunks' => ['name' => 'Building knowledge chunks', 'weight' => 35],
        'finalize' => ['name' => 'Finalizing scan', 'weight' => 5],
    ],

];
