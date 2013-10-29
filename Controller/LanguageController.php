<?php
// LanguageController.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 26/10/13
 * Time: 11:45
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\TranslationBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class LanguageController
 * @package Gizlab\Bundle\TranslationBundle\Controller
 *
 */
class LanguageController extends Controller
{

    /**
     * Internal route
     */
    public function renderAction()
    {
        $languages = $this->getDoctrine()->getManager()->getRepository('GizlabTranslationBundle:Language')->findAll();


    }

}