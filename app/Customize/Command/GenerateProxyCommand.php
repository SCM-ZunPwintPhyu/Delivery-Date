<?php

namespace Customize\Command;

use Customize\Service\EntityProxyReplaceService;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Eccube\Service\EntityProxyService;
use Customize\Service\RepositoryProxyService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateProxyCommand extends ContainerAwareCommand
{
    protected static $defaultName = 'eccube:generate:proxies';

    /**
     * @var EntityProxyService
     */
    private $entityProxyService;

    /**
     * @var EntityProxyReplaceService
     */
    private $entityProxyReplace;

    /**
     * @var RepositoryProxyService
     */
    private $repoProxyService;

    public function __construct(EntityProxyService $entityProxyService, EntityProxyReplaceService $entityProxyReplace, RepositoryProxyService $repoProxyService)
    {
        parent::__construct();
        $this->entityProxyService = $entityProxyService;
        $this->entityProxyReplace = $entityProxyReplace;
        $this->repoProxyService = $repoProxyService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate proxies');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $projectDir = $container->getParameter('kernel.project_dir');
        $enabledPlugins = $container->getParameter('eccube.plugins.enabled');

        $this->proxyEntityReplace($projectDir, $output);
        
        $this->proxyEntity($projectDir, $enabledPlugins, $output);

        $this->proxyRepositoryReplace($projectDir, $output);
    }

    protected function proxyEntity($projectDir, $enabledPlugins, $output)
    {
        $includeDirs = [$projectDir . '/app/Customize/Entity'];
        foreach ($enabledPlugins as $code) {
            if (file_exists($projectDir . '/app/Plugin/' . $code . '/Entity')) {
                $includeDirs[] = $projectDir . '/app/Plugin/' . $code . '/Entity';
            }
        }

        $this->entityProxyService->generate(
            $includeDirs,
            [],
            $projectDir . '/app/proxy/entity',
            $output
        ); 
    }

    protected function proxyEntityReplace($projectDir, $output)
    {
        $includeDirs = [$projectDir . '/app/Customize/EntityReplace'];

        $this->entityProxyReplace->generate(
            $includeDirs,
            [],
            $projectDir . '/app/proxy/entity',
            $output
        );
    }

    protected function proxyRepositoryReplace($projectDir, $output)
    {
        $includeDirs = [$projectDir . '/app/Customize/Repository'];

        $this->repoProxyService->generate(
            $includeDirs,
            [],
            $projectDir . '/app/proxy/repository',
            $output
        );
    }
}
