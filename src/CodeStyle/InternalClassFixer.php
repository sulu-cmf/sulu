<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Gert de Pagter <BackEndTea@gmail.com>
 *
 * @implements ConfigurableFixerInterface<_AutogeneratedInputConfiguration, _AutogeneratedComputedConfiguration>
 *
 * @phpstan-type _AutogeneratedInputConfiguration array{
 *  types?: list<'abstract'|'final'|'normal'>
 * }
 * @phpstan-type _AutogeneratedComputedConfiguration array{
 *  types: list<'abstract'|'final'|'normal'>
 * }
 */
final class InternalClassFixer extends AbstractFixer implements WhitespacesAwareFixerInterface, ConfigurableFixerInterface
{
    /** @use ConfigurableFixerTrait<_AutogeneratedInputConfiguration, _AutogeneratedComputedConfiguration> */
    use ConfigurableFixerTrait;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('All PHPUnit test classes should be marked as internal.');
    }

    /**
     * {@inheritdoc}
     *
     * Must run before FinalInternalClassFixer, PhpdocSeparationFixer.
     */
    public function getPriority(): int
    {
        return 68;
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('base_type', 'What types of classes to mark as internal.'))
                ->setAllowedTypes(['string[]'])
                ->getOption(),
            (new FixerOptionBuilder('excluded_files', 'What files should be skipped'))
                ->setAllowedTypes(['string[]'])
                ->setDefault([])
                ->getOption(),
        ]);
    }

    public function getName(): string
    {
        return 'Sulu/internal_class';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([\T_CLASS, \T_STRING]);
    }

    public function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        $basePathLength = \strlen(\dirname(\dirname(__DIR__)) . '1');
        $relativePath = \substr($file->getRealPath(), $basePathLength);
        if (\in_array($relativePath, $this->configuration['excluded_files'] ?? [])) {
            return;
        }

        // Getting the locations of the class, implements, extends keywords and the beginning of the class body
        $classIndex = $tokens->getNextTokenOfKind(0, [[\T_CLASS]]);
        $impementsIndex = $tokens->getNextTokenOfKind($classIndex, [[\T_IMPLEMENTS]]);
        $extendsIndex = $tokens->getNextTokenOfKind($classIndex, [[\T_EXTENDS]]);
        $classBody = $tokens->getNextTokenOfKind($classIndex, ['{']);

        if (!$this->shouldRefactor($tokens, $impementsIndex, $extendsIndex, $classBody)) {
            return;
        }

        // Adding the @internal keywords to the class
        $this->ensureIsDocBlockWithAnnotation(
            $tokens,
            $classIndex,
            'internal',
            ['internal'],
            [],
        );
    }

    /**
     * Determines if a class should be refactored.
     * It should not be refactored if it does not implement or extend anything
     * It should be refactored if one of the implemented Interfaces or extended classes are in the base_class list.
     *
     * Limitations: Not checking namespaces, aliased imports
     */
    private function shouldRefactor(Tokens $tokens, ?int $impementsIndex, ?int $extendsIndex, int $end): bool
    {
        // No implements and no extends means no reason for internal
        if ($impementsIndex === $extendsIndex && null === $extendsIndex) {
            return false;
        }

        if ($extendsIndex) {
            for ($i = $extendsIndex + 1; $i < ($impementsIndex ?? $end); ++$i) {
                if (\in_array($tokens[$i]->getContent(), $this->configuration['base_type'])) {
                    return true;
                }
            }
        }

        if ($impementsIndex) {
            for ($i = $impementsIndex + 1; $i < $end; ++$i) {
                if (\in_array($tokens[$i]->getContent(), $this->configuration['base_type'])) {
                    return true;
                }
            }
        }

        return false;
    }

    // ============================ THE FOLLOWING METHODS ARE COPIES FROM PhpUnitFixer ===============================

    /**
     * @param list<string> $preventingAnnotations
     * @param list<class-string> $preventingAttributes
     */
    final protected function ensureIsDocBlockWithAnnotation(
        Tokens $tokens,
        int $index,
        string $annotation,
        array $preventingAnnotations,
        array $preventingAttributes
    ): void {
        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);

        if ($this->isPHPDoc($tokens, $docBlockIndex)) {
            $this->updateDocBlockIfNeeded($tokens, $docBlockIndex, $annotation, $preventingAnnotations);
        } else {
            $this->createDocBlock($tokens, $docBlockIndex, $annotation);
        }
    }

    final protected function getDocBlockIndex(Tokens $tokens, int $index): int
    {
        $modifiers = [\T_PUBLIC, \T_PROTECTED, \T_PRIVATE, \T_FINAL, \T_ABSTRACT, \T_COMMENT, \T_ATTRIBUTE];

        if (\defined('T_READONLY')) { // @TODO: drop condition when PHP 8.2+ is required
            $modifiers[] = \T_READONLY;
        }

        do {
            $index = $tokens->getPrevNonWhitespace($index);

            if ($tokens[$index]->isGivenKind(CT::T_ATTRIBUTE_CLOSE)) {
                $index = $tokens->getPrevTokenOfKind($index, [[\T_ATTRIBUTE]]);
            }
        } while ($tokens[$index]->isGivenKind($modifiers));

        return $index;
    }

    final protected function isPHPDoc(Tokens $tokens, int $index): bool
    {
        return $tokens[$index]->isGivenKind(\T_DOC_COMMENT);
    }

    /**
     * @param list<string> $preventingAnnotations
     */
    private function updateDocBlockIfNeeded(
        Tokens $tokens,
        int $docBlockIndex,
        string $annotation,
        array $preventingAnnotations
    ): void {
        $doc = new DocBlock($tokens[$docBlockIndex]->getContent());
        foreach ($preventingAnnotations as $preventingAnnotation) {
            if ([] !== $doc->getAnnotationsOfType($preventingAnnotation)) {
                return;
            }
        }
        $doc = $this->makeDocBlockMultiLineIfNeeded($doc, $tokens, $docBlockIndex, $annotation);

        $lines = $this->addInternalAnnotation($doc, $tokens, $docBlockIndex, $annotation);
        $lines = \implode('', $lines);

        $tokens[$docBlockIndex] = new Token([\T_DOC_COMMENT, $lines]);
    }

    private function createDocBlock(Tokens $tokens, int $docBlockIndex, string $annotation): void
    {
        $lineEnd = $this->whitespacesConfig->getLineEnding();
        $originalIndent = WhitespacesAnalyzer::detectIndent($tokens, $tokens->getNextNonWhitespace($docBlockIndex));
        $toInsert = [
            new Token([\T_DOC_COMMENT, "/**{$lineEnd}{$originalIndent} * @{$annotation}{$lineEnd}{$originalIndent} */"]),
            new Token([\T_WHITESPACE, $lineEnd . $originalIndent]),
        ];
        $index = $tokens->getNextMeaningfulToken($docBlockIndex);
        $tokens->insertAt($index, $toInsert);

        if (!$tokens[$index - 1]->isGivenKind(\T_WHITESPACE)) {
            $extraNewLines = $this->whitespacesConfig->getLineEnding();

            if (!$tokens[$index - 1]->isGivenKind(\T_OPEN_TAG)) {
                $extraNewLines .= $this->whitespacesConfig->getLineEnding();
            }

            $tokens->insertAt($index, [
                new Token([\T_WHITESPACE, $extraNewLines . WhitespacesAnalyzer::detectIndent($tokens, $index)]),
            ]);
        }
    }

    /**
     * @return list<Line>
     */
    private function addInternalAnnotation(DocBlock $docBlock, Tokens $tokens, int $docBlockIndex, string $annotation): array
    {
        $lines = $docBlock->getLines();
        $originalIndent = WhitespacesAnalyzer::detectIndent($tokens, $docBlockIndex);
        $lineEnd = $this->whitespacesConfig->getLineEnding();
        \array_splice($lines, -1, 0, $originalIndent . ' * @' . $annotation . $lineEnd);

        return $lines;
    }

    private function makeDocBlockMultiLineIfNeeded(DocBlock $doc, Tokens $tokens, int $docBlockIndex, string $annotation): DocBlock
    {
        $lines = $doc->getLines();
        if (1 === \count($lines) && [] === $doc->getAnnotationsOfType($annotation)) {
            $indent = WhitespacesAnalyzer::detectIndent($tokens, $tokens->getNextNonWhitespace($docBlockIndex));
            $doc->makeMultiLine($indent, $this->whitespacesConfig->getLineEnding());

            return $doc;
        }

        return $doc;
    }
}
