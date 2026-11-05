<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/modules',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/src/Kernel.php',
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        // deptrac's PHP parser does not accept `new X(...)->method()` yet; keep explicit parentheses.
        NewMethodCallWithoutParenthesesRector::class,
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withCache(__DIR__ . '/var/rector');
