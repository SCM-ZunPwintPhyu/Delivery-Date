<?php

namespace Customize\Service;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\Token;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProxyReplacer
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * ProxyReplacer constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 複数のディレクトリセットをスキャンしてディレクトリセットごとのclassesのマッピングを返します.
     *
     * @param $dirSets array スキャン対象ディレクトリリストの配列
     *
     * @return array ディレクトリセットごとのclassesのマッピング
     */
    protected function scanClasses($dirSets, $annoClass)
    {
        // ディレクトリセットごとのファイルをロードしつつ一覧を作成
        $includedFileSets = [];
        foreach ($dirSets as $dirSet) {
            $includedFiles = [];
            $dirs = array_filter($dirSet, 'file_exists');
            if (!empty($dirs)) {
                $files = Finder::create()
                    ->in($dirs)
                    ->name('*.php')
                    ->files();

                foreach ($files as $file) {
                    require_once $file->getRealPath();
                    $includedFiles[] = $file->getRealPath();
                }
            }
            $includedFileSets[] = $includedFiles;
        }

        $declaredClasses = array_map(function ($fqcn) {
            return strpos($fqcn, '\\') === 0 ? $fqcn : '\\' . $fqcn;
        }, \get_declared_classes());


        $classSets = array_map(function () {return [];}, $dirSets);

        foreach ($declaredClasses as $className) {
            $rc = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();
            foreach ($includedFileSets as $index => $includedFiles) {
                if (in_array($sourceFile, $includedFiles)) {
                    $classSets[$index][] = $className;
                }
            }
        }

        $reader = new AnnotationReader();
        $proxySets = [];
        foreach ($classSets as $classes) {
            $proxies = [];
            foreach ($classes as $class) {
                $anno = $reader->getClassAnnotation(new \ReflectionClass($class), $annoClass);
                if ($anno) {
                    $proxies[$anno->value][] = $class;
                }
            }
            $proxySets[] = $proxies;
        }
        return $proxySets;
    }

    /**
     * ClassにTraitを追加.
     *
     * @param Tokens $classTokens Tokens Classのトークン
     * @param $trait string 追加するTraitのFQCN
     */
    protected function replaceNamespace($classTokens, $trait)
    {
        $namespaceTokens = $this->convertNameSpaceToTokens($trait);

        $namespaceTokens = array_merge(
            [
                new Token([T_NAMESPACE, 'namespace']),
                new Token([T_WHITESPACE, ' ']),
            ],
            $namespaceTokens,
            [new Token(';')]
        );

        $namespaceStart = $classTokens->getNextTokenOfKind(0, [[T_NAMESPACE]]);
        $namespaceEnd = $classTokens->getNextTokenOfKind($namespaceStart, [';']);

        $classTokens->overrideRange($namespaceStart, $namespaceEnd, $namespaceTokens);
    }

    /**
     * Convert Namespace to tokens
     * @param $name
     *
     * @return array|Token[]
     */
    protected function convertNameSpaceToTokens($name)
    {
        $result = [];
        $i = 0;
        foreach (explode('\\', $name) as $part) {
            // プラグインのtraitの場合は、0番目は空文字
            // 本体でuseされているtraitは0番目にtrait名がくる
            if ($part) {
                // プラグインのtraitの場合はFQCNにする
                if ($i > 0) {
                    $result[] = new Token([T_NS_SEPARATOR, '\\']);
                }
                $result[] = new Token([T_STRING, $part]);
            }
            $i++;
        }

        return $result;
    }

    /**
     * remove block to 'if (!class_exists(<class name>)) { }'
     *
     * @param Tokens $classTokens
     */
    protected function removeClassExistsBlock(Tokens $classTokens)
    {
        $startIndex = $classTokens->getNextTokenOfKind(0, [[T_IF]]);
        $classIndex = $classTokens->getNextTokenOfKind(0, [[T_CLASS]]);
        if ($startIndex > 0 && $startIndex < $classIndex) { // if statement before class
            $blockStartIndex = $classTokens->getNextTokenOfKind($startIndex, ['{']);
            $blockEndIndex = $classTokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $blockStartIndex);

            $classTokens->clearRange($startIndex, $blockStartIndex);
            $classTokens->clearRange($blockEndIndex, $blockEndIndex + 1);
        }
    }
    
}