<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'bootstrap/cache',
        'storage',
        'vendor',
        'node_modules',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        
        // Array notation
        'array_syntax' => ['syntax' => 'short'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'normalize_index_brace' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        
        // Basic formatting
        'blank_line_after_opening_tag' => true,
        'braces' => [
            'allow_single_line_closure' => true,
            'position_after_functions_and_oop_constructs' => 'next',
        ],
        'cast_spaces' => ['space' => 'single'],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'none'],
        'function_typehint_space' => true,
        'include' => true,
        'lowercase_cast' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'native_function_casing' => true,
        'new_with_braces' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_list_call' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_scalar' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'return_type_declaration' => true,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'single_class_element_per_statement' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'single_quote' => true,
        'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'unary_operator_spaces' => true,
        
        // Laravel specific
        'not_operator_with_successor_space' => false,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'method_chaining_indentation' => true,
        'single_trait_insert_per_statement' => true,
        
        // Modern PHP features
        'list_syntax' => ['syntax' => 'short'],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => ['target' => 'newest'],
        'php_unit_expectation' => ['target' => 'newest'],
        'php_unit_fqcn_annotation' => true,
        'php_unit_mock' => ['target' => 'newest'],
        'php_unit_namespaced' => ['target' => 'newest'],
        'php_unit_no_expectation_annotation' => ['target' => 'newest'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
        
        // Strict types
        'declare_strict_types' => false, // Laravel doesn't use strict types by default
        
        // Disable some rules that conflict with Laravel conventions
        'yoda_style' => false,
        'increment_style' => false,
        'not_operator_with_space' => false,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache'); 