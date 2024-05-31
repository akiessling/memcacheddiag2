<?php

declare(strict_types=1);


return [
    'tools_memcacheddiag2' => [
        'path' => '/module/memcacheddiag2',
        'parent' => 'tools',
        'access' => 'systemMaintainer',
        'extensionName' => 'memcacheddiag2',
        'icon' => 'EXT:memcacheddiag2/Resources/Public/Icons/Extension.gif',
        'labels' => 'LLL:EXT:memcacheddiag2/Resources/Private/Language/locallang_module.xlf',
        'controllerActions' => [
            \JBartels\Memcacheddiag2\Controller\MemcacheController::class => [
                'list',
            ],
        ],
    ],
];
