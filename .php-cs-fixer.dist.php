<?php

$header = <<<EOF
This file is part of Sulu.

(c) Sulu GmbH

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'class_definition' => false,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'function_declaration' => ['closure_function_spacing' => 'none'],
        'header_comment' => ['header' => $header],
        'native_function_invocation' => ['include' => ['@internal']],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'phpdoc_types_order' => false,
        'single_line_throw' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'get_class_to_class_keyword' => false, // should be enabled as soon as support for php < 8 is dropped
        'nullable_type_declaration_for_default_null_value' => true,
        'no_null_property_initialization' => false,
        'new_with_parentheses' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->exclude('cache')
            ->exclude('Tests/reports/')
            ->in(__DIR__)
    );

return $config;
