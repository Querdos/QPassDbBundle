<?php

namespace Querdos\QPassDbBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class QPassDbExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $db_dir = $this->format_path(
            $config['db_dir'],
            $container->getParameter('kernel.root_dir')
        );

        $container->setParameter('q_pass_db.db_dir', $db_dir);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * Format a given path to absolute one
     *  - For example, will transform ~/Documents/.gnupg as /home/user/Documents/.gnupg
     *
     * @param string $path
     * @param string $rootDir
     *
     * @return string
     */
    private function format_path($path, $rootDir)
    {
        // retrieving current user
        $user  = exec('whoami');

        // building replacement pattern
        $rep_1     = sprintf('/home/%s${1}', $user);
        $rep_slash = '${1}'; // used to remove the / character at the end (eventually)
        $rep_2     = sprintf('%s/../${1}', $rootDir);

        // building regex pattern
        $pat_1     = '/^\~(.*)\/{0,1}/';
        $pat_slash = '/(.*)\/$/';
        $pat_2     = '/(^[A-Za-z].*)/';

        $formatted = preg_replace($pat_slash, $rep_slash, $path);

        $formatted = preg_replace($pat_1, $rep_1, $formatted);
        if ($formatted !== $path) return $formatted;

        $formatted = preg_replace($pat_2, $rep_2, $formatted);
        if ($formatted !== $path) return $formatted;

        return $formatted;
    }
}
