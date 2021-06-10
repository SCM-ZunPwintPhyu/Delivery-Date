<?php

namespace Customize\Service;

use Customize\Annotation\RepositoryReplace;
use PhpCsFixer\Tokenizer\Tokens;
use Zend\Code\Reflection\ClassReflection;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RepositoryProxyService extends ProxyReplacer
{
    /**
     * RepositoryのProxyを生成します。
     *
     * @param array $includesDirs 
     * @param array $excludeDirs
     * @param string $outputDir 出力先
     * @param OutputInterface $output ログ出力
     *
     * @return array 生成したファイルのリスト
     */
    public function generate($includesDirs, $excludeDirs, $outputDir, OutputInterface $output = null)
    {
        if (is_null($output)) {
            $output = new ConsoleOutput();
        }

        $generatedFiles = [];

        list($addClasses, $removeClasses) = $this->scanClasses([$includesDirs, $excludeDirs], RepositoryReplace::class);

        $targetRepositories = array_unique(array_merge(array_keys($addClasses), array_keys($removeClasses)));

        // プロキシファイルの生成

        foreach ($targetRepositories as $targetRepo) {
            $classes = isset($addClasses[$targetRepo]) ? $addClasses[$targetRepo] : [];

            if (count($classes) < 1) {
                continue;
            }

            $rc = new ClassReflection($targetRepo);
            $fileName = str_replace('\\', '/', $rc->getFileName());
            $baseName = basename($fileName);

            $replaceClass = new ClassReflection($classes[0]);
            $rcfileName = str_replace('\\', '/', $replaceClass->getFileName());
            $repositoryTokens = Tokens::fromCode(file_get_contents($rcfileName));

            if (strpos($fileName, 'app/proxy/repository') === false) {
                $this->removeClassExistsBlock($repositoryTokens); // remove class_exists block
            } else {
                // Remove to duplicate path of /app/proxy/repository
                $fileName = str_replace('/app/proxy/repository', '', $fileName);
            }

            $this->replaceNamespace($repositoryTokens, $rc->getNamespaceName());

            $projectDir = str_replace('\\', '/', $this->container->getParameter('kernel.project_dir'));

            $baseDir = str_replace($projectDir, '', str_replace($baseName, '', $fileName));
            if (!file_exists($outputDir . $baseDir)) {
                mkdir($outputDir . $baseDir, 0777, true);
            }

            $file = ltrim(str_replace($projectDir, '', $fileName), '/');
            $code = $repositoryTokens->generateCode();
            $generatedFiles[] = $outputFile = $outputDir . '/' . $file;

            file_put_contents($outputFile, $code);
            $output->writeln('gen -> ' . $outputFile);
        }

        return $generatedFiles;
    }
}
