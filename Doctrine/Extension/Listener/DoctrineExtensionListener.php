<?php
// DoctrineExtensionListener.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 7/26/13
 * Time: 1:51 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Gizlab\Bundle\TranslationBundle\Doctrine\Extension\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DoctrineExtensionListener
 * @package Gizlab\Bundle\TranslationBundle\Doctrine\Extension\Listener
 */
class DoctrineExtensionListener
{
    /**
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\DependencyInjection\ContainerAwareInterface::setContainer()
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set locale for translatable listener
     *
     * @param GetResponseEvent $event
     */
    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($event->getRequest()->getLocale());
    }
}