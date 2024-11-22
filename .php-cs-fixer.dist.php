<?php declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('tools')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'standardize_increment' => false,
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
        ],
    ])
    ->setFinder($finder)
    ;
