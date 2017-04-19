<?php
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),

            new Querdos\QPassDbBundle\QPassDbBundle()
        ];
    }
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
    /**
     * @return string
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/QPassDbBundle/cache';
    }
    /**
     * @return string
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/QPassDbBundle/logs';
    }
}
