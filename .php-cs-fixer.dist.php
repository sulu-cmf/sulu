<?php

$header = <<<EOF
This file is part of Sulu.

(c) Sulu GmbH

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->exclude(['Tests/Application/var/cache'])
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_definition' => false,
        'concat_space' => ['spacing' => 'one'],
        'function_declaration' => ['closure_function_spacing' => 'none'],
        'header_comment' => ['header' => $header],
        'native_constant_invocation' => true,
        'native_function_casing' => true,
        'native_function_invocation' => ['include' => ['@internal']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'ordered_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_types_order' => false,
        'single_line_throw' => false,
        'single_line_comment_spacing' => false,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['todo', 'var', 'see', 'phpstan-ignore-next-line', 'phpstan-use'],
        ],
    ])
    ->setFinder($finder);

return $config;
