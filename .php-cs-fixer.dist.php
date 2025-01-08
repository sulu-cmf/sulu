<?php

$header = <<<EOF
This file is part of Sulu.

(c) Sulu GmbH

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->exclude(['var/cache', 'tests/Resources/cache', 'node_modules'])
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
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
        'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'remove_inheritdoc' => true],
        'ordered_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_types_order' => false,
        'single_line_throw' => false,
        'single_line_comment_spacing' => false,
        'phpdoc_to_comment' => [
            'ignored_tags' => ['todo', 'var', 'see'],
        ],
        'phpdoc_separation' => [
            'groups' => [
                ['Serializer\\*', 'VirtualProperty', 'Accessor', 'Type', 'Groups', 'Expose', 'Exclude', 'SerializedName', 'Inline', 'ExclusionPolicy'],
            ],
        ],
        'get_class_to_class_keyword' => false, // should be enabled as soon as support for php < 8 is dropped
        'nullable_type_declaration_for_default_null_value' => true,
        'no_null_property_initialization' => false,
        'fully_qualified_strict_types' => false,
        'new_with_parentheses' => true,
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['array_destructuring', 'arrays', 'match']],
    ])
    ->setFinder($finder);

return $config;
