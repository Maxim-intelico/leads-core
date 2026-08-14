<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src');

return new PhpCsFixer\Config()
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setCacheFile('.php-cs-fixer.cache')
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => false,
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'class_definition' => false,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_align' => false,
        'yoda_style' => false,
        'no_break_comment' => false,
        'no_superfluous_phpdoc_tags' => false,
        'php_unit_fqcn_annotation' => true,
        'no_empty_phpdoc' => true,
        'no_unused_imports' => true,
        'self_accessor' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_trim' => true,
        'phpdoc_separation' => true,
        'fully_qualified_strict_types' => true,
        'phpdoc_single_line_var_spacing' => true,
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'phpdoc_order' => true,
        'declare_strict_types' => true,
        'single_line_throw' => false,
        'cast_spaces' => ['space' => 'none'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'function_declaration' => false,
        'increment_style' => false, // можно попробовать вернуть на true
        'blank_line_between_import_groups' => false,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_line_span' => [
            'method' => 'multi',
            'property' => 'single',
        ],
        'new_expression_parentheses' => [
            'use_parentheses' => false,
        ],
        'no_useless_else' => false,
        'operator_linebreak' => ['only_booleans' => true],
    ])
    ->setFinder($finder);
