<?php
// LanguageExtension.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 26/10/13
 * Time: 12:14
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\TranslationBundle\Extension\Twig;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LanguageExtension
 * @package Gizlab\Bundle\TranslationBundle\Extension\Twig
 *
 * Extension for rendering languages
 */
class LanguageExtension extends \Twig_Extension
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * Default template for render languages
     *
     * @var string
     */
    protected $template = 'GizlabTranslationBundle:Language:_languages.html.twig';

    /**
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * @var \JMS\I18nRoutingBundle\Router\I18nRouter
     */
    protected $router;

    /**
     * @param $defaultLocale
     * @param $request
     * @param $entityManager
     * @param null $defaultTemplate
     */
    public function __construct(ContainerInterface $container, $template = null)
    {
        /*
         * Get default locale
         */
        $this->defaultLocale = $container->getParameter('locale');

        /*
         * Init entity manager
         */
        $this->entityManager = $container->get('doctrine.orm.default_entity_manager');

        /*
         * Get request
         */
        if ($container->isScopeActive('request')){
            $this->request = $container->get('request');
        }

        $this->router = $container->get('router');

        $this->template = $template;

    }

    /*
     * Extending \Twig_Extension
     */

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @see \Twig_Extension::getName()
     * @return string
     */
    public function getName()
    {
        return 'languages';
    }

    /**
     * @see \Twig_Extension::getFunctions()
     * @return array
     */
    public function getFunctions()
    {
        return array('render_languages' => new \Twig_Function_Method($this, 'renderLanguages'));
    }

    /**
     * Render languages
     */
    public function renderLanguages()
    {
        /*
         * Get languages from database
         */
        $languages = $this->entityManager->getRepository('GizlabTranslationBundle:Language')->findAll();

        $currentLocale = $this->request->getLocale();

        /*
         * Processing default locale
         */
        $params = $this->request->attributes->get('_route_params');

        $route = $this->request->attributes->get('_route');


        $links = array();
        foreach($languages as $language){
            $params['_locale'] = $language->getId();


            $url = $this->router->generate($route, $params);

            $link = array('label' => $language->getLabel(), 'is_active' => false, 'url' => $url);

            if ($language->getId() == $currentLocale){
                $link['is_active'] = true;
            }
            $links[] = $link;
        }


        /*
         * Render languages
         */

        echo $this->environment->render($this->template, array('links'=>$links));

    }

}