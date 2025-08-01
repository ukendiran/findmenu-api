<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'FindMenu API Docs',
            ],

            'routes' => [
                'api' => 'api/documentation',
            ],

            'paths' => [
                'use_absolute_path' => true,
                'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
                'docs_json' => 'api-docs.json', // ✅ Corrected filename
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => 'json',

                // ✅ Scan annotations from these folders
                'annotations' => [
                    base_path('app/Http/Controllers'),
                    base_path('app/Swagger/Schemas'),  // ✅ make sure this is included
                ],
                'excludes' => [],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'docs' => ['web'],      // 🔐 Protect Swagger UI with session auth
                'api' => ['web', 'auth'],       // 🔐 Protect OpenAPI JSON with session auth
                'asset' => [],                  // Optional: can be left open or protected
            ],
        ],

        'paths' => [
            'docs' => base_path('public'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'excludes' => [],
        ],

        'scanOptions' => [
            'exclude' => [],
            'open_api_spec_version' => \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION,
        ],

        'securityDefinitions' => [
            'securitySchemes' => [
                'bearerAuth' => [ // ✅ This is needed for @security={{"bearerAuth":{}}}
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'Enter token in format: Bearer {token}',
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
            ],
        ],

        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true), // ✅ Dev only
        'generate_yaml_copy' => false,

        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,

        'ui' => [
            'display' => [
                'doc_expansion' => 'none',
                'filter' => true,
                'dark_mode' => false,
            ],
            'authorization' => [
                'persist_authorization' => false,
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('API_URL', 'http://localhost'),
        ],
    ],

    'paths' => [
        base_path('app/Http/Controllers/Api'),
        base_path('app/Swagger'),
    ],

    'output' => public_path('docs/api-docs.json'),
];
