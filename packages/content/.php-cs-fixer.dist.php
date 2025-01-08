<?php

$header = <<<EOF
This file is part of Sulu.

(c) Sulu GmbH

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_definition' => false,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'function_declaration' => ['closure_function_spacing' => 'none'],
        'header_comment' => ['header' => $header],
        'native_constant_invocation' => true,
        'native_function_casing' => true,
        'native_function_invocation' => ['include' => ['@internal']],
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'ordered_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'php_unit_strict' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_order' => true,
        'phpdoc_types_order' => false,
        'single_line_throw' => false,
        'single_line_comment_spacing' => false,
        'strict_comparison' => true,
        'strict_param' => true,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['todo', 'var'],
        ],
        'get_class_to_class_keyword' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'no_null_property_initialization' => false,
        'fully_qualified_strict_types' => false,
        'new_with_parentheses' => true,
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['array_destructuring', 'arrays', 'match']],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->exclude('cache')
            ->exclude('Tests/reports/')
            ->in(__DIR__)
    );

return $config;
