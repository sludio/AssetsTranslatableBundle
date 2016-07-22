<?php

namespace Assets\TranslatableBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Assets\TranslatableBundle\DependencyInjection\Compiler\TemplatingPass;

class AssetsTranslatableBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new TemplatingPass());
    }
}
