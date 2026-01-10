<?php

return [

    'storage_path' => storage_path('app/projects'),

    /*
    |--------------------------------------------------------------------------
    | Exclusion Configuration
    |--------------------------------------------------------------------------
    */

    'exclusions' => [

        // Schema version for tracking changes
        'schema_version' => '2.0.0',

        // Directory exclusions (exact match against any path segment)
        'directories' => [
            // Version control
            '.git',
            '.svn',
            '.hg',

            // Dependencies
            'vendor',
            'node_modules',
            'bower_components',

            // Laravel specific
            'storage',
            'bootstrap/cache',

            // Build outputs
            'public/build',
            'public/hot',
            'dist',
            'build',
            '.output',
            '.next',
            '.nuxt',

            // IDE/Editor
            '.idea',
            '.vscode',
            '.fleet',

            // Cache directories
            'cache',
            '.cache',
            '__pycache__',
            '.pytest_cache',
            '.mypy_cache',
            '.phpunit.cache',

            // Coverage/Reports
            'coverage',
            '.nyc_output',
        ],

        // Path pattern exclusions (glob-style)
        'patterns' => [
            '**/node_modules/**',
            '**/vendor/**',
            '**/.git/**',
            '**/storage/logs/**',
            '**/storage/framework/**',
            '**/bootstrap/cache/**',
        ],

        // File name exclusions (exact match)
        'files' => [
            '.DS_Store',
            'Thumbs.db',
            '.gitkeep',
            '.gitignore',
            '.editorconfig',
        ],

        // Extension exclusions
        'extensions' => [
            // Lock files
            'lock',

            // Logs
            'log',

            // Source maps
            'map',

            // Minified files (pattern-based)
            'min.js',
            'min.css',

            // Compiled assets
            'bundle.js',
            'chunk.js',
        ],

        // Binary extensions (content not scanned)
        'binary_extensions' => [
            // Images
            'png', 'jpg', 'jpeg', 'gif', 'bmp', 'ico', 'webp', 'svg', 'avif', 'tiff',

            // Audio/Video
            'mp3', 'mp4', 'wav', 'avi', 'mov', 'mkv', 'webm', 'ogg', 'flac',

            // Documents
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',

            // Archives
            'zip', 'tar', 'gz', 'rar', '7z', 'bz2', 'xz',

            // Executables
            'exe', 'dll', 'so', 'dylib', 'bin', 'app',

            // Fonts
            'ttf', 'otf', 'woff', 'woff2', 'eot',

            // Databases
            'sqlite', 'db', 'mysql', 'sqlite3', 'mdb',

            // Other
            'phar', 'jar', 'war',
        ],

        // Configurable toggles
        'toggles' => [
            'include_vendor' => false,
            'include_node_modules' => false,
            'include_storage' => false,
            'include_build_output' => false,
            'include_lock_files' => false,
            'include_source_maps' => false,
            'include_minified' => false,
        ],

        // Per-project override capability
        'allow_project_overrides' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Size Limits
    |--------------------------------------------------------------------------
    */

    'max_file_size' => env('PROJECT_MAX_FILE_SIZE', 1024 * 1024), // 1MB
    'warn_file_size' => env('PROJECT_WARN_FILE_SIZE', 512 * 1024), // 512KB

    /*
    |--------------------------------------------------------------------------
    | Chunking Configuration
    |--------------------------------------------------------------------------
    */

    'chunking' => [
        'max_bytes' => env('PROJECT_CHUNK_MAX_BYTES', 200 * 1024), // 200KB
        'max_lines' => env('PROJECT_CHUNK_MAX_LINES', 500),
        'min_lines' => env('PROJECT_CHUNK_MIN_LINES', 10),
        'overlap_lines' => env('PROJECT_CHUNK_OVERLAP', 0), // No overlap by default

        // Break point preferences (higher = more preferred)
        'break_weights' => [
            'empty_line' => 10,
            'function_boundary' => 8,
            'class_boundary' => 9,
            'block_end' => 7,
            'comment_block' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Detection
    |--------------------------------------------------------------------------
    */

    'languages' => [
        'extension_map' => [
            'php' => 'php',
            'blade.php' => 'blade',
            'js' => 'javascript',
            'mjs' => 'javascript',
            'cjs' => 'javascript',
            'ts' => 'typescript',
            'mts' => 'typescript',
            'tsx' => 'typescriptreact',
            'jsx' => 'javascriptreact',
            'vue' => 'vue',
            'svelte' => 'svelte',
            'css' => 'css',
            'scss' => 'scss',
            'sass' => 'sass',
            'less' => 'less',
            'json' => 'json',
            'yml' => 'yaml',
            'yaml' => 'yaml',
            'md' => 'markdown',
            'mdx' => 'mdx',
            'sql' => 'sql',
            'sh' => 'shell',
            'bash' => 'shell',
            'zsh' => 'shell',
            'xml' => 'xml',
            'html' => 'html',
            'twig' => 'twig',
            'env' => 'dotenv',
            'env.example' => 'dotenv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Framework Detection
    |--------------------------------------------------------------------------
    */

    'framework_hints' => [
        'path_patterns' => [
            'livewire' => [
                'app/Livewire/*',
                'app/Http/Livewire/*',
                'resources/views/livewire/*',
            ],
            'inertia' => [
                'resources/js/Pages/*',
                'resources/js/pages/*',
                'resources/ts/Pages/*',
                'resources/ts/pages/*',
            ],
            'blade' => [
                'resources/views/*.blade.php',
                'resources/views/**/*.blade.php',
            ],
            'vue' => [
                'resources/js/**/*.vue',
                'resources/ts/**/*.vue',
            ],
            'react' => [
                'resources/js/**/*.jsx',
                'resources/js/**/*.tsx',
                'resources/ts/**/*.tsx',
            ],
        ],
        'content_markers' => [
            'livewire' => [
                'extends Livewire\\Component',
                'use Livewire\\',
                '@livewire(',
                '<livewire:',
            ],
            'inertia' => [
                'Inertia::render',
                '@inertia',
                'createInertiaApp',
                'usePage(',
            ],
            'vue' => [
                'defineComponent',
                '',
                'createApp(',
                'Vue.component',
            ],
            'react' => [
                'React.',
                'useState',
                'useEffect',
                'createRoot',
                '',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Configuration
    |--------------------------------------------------------------------------
    */

    'pipeline_stages' => [
        'workspace' => ['name' => 'Preparing workspace', 'weight' => 5],
        'clone' => ['name' => 'Cloning repository', 'weight' => 15],
        'manifest' => ['name' => 'Building file manifest', 'weight' => 30],
        'stack' => ['name' => 'Detecting stack', 'weight' => 10],
        'chunks' => ['name' => 'Building knowledge chunks', 'weight' => 35],
        'finalize' => ['name' => 'Finalizing scan', 'weight' => 5],
    ],

    'github_webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
];
