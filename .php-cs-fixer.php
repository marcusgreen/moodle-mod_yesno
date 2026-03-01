<?php
// PHP CS Fixer configuration for mod_yesno.
// Aligns with Moodle coding standards where possible.

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('node_modules');

return (new PhpCsFixer\Config())
    ->setRules([
        // PSR-12 base (closest standard to Moodle's style).
        '@PSR12'                         => true,

        // Indentation: 4 spaces (no tabs).
        'indentation_type'               => true,

        // Line endings: LF only.
        'line_ending'                    => true,

        // Trailing whitespace.
        'no_trailing_whitespace'         => true,
        'no_trailing_whitespace_in_comment' => true,

        // Single quotes for plain strings.
        'single_quote'                   => true,

        // Trailing commas in multi-line arrays.
        'trailing_comma_in_multiline'    => ['elements' => ['arrays']],

        // Spaces around operators.
        'binary_operator_spaces'         => ['default' => 'single_space'],

        // No blank line after opening brace.
        'no_blank_lines_after_class_opening' => true,

        // Single blank line at end of file.
        'single_blank_line_at_eof'       => true,

        // No unused imports.
        'no_unused_imports'              => true,

        // Ordered imports.
        'ordered_imports'                => ['sort_algorithm' => 'alpha'],

        // Concat spacing: spaces around . operator.
        'concat_space'                   => ['spacing' => 'one'],

        // Cast spacing.
        'cast_spaces'                    => ['space' => 'single'],

        // Array syntax: short [].
        'array_syntax'                   => ['syntax' => 'short'],
    ])
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setFinder($finder);
