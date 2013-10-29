<?php
namespace Gizlab\Bundle\TranslationBundle\Extension\Twig;

/**
 * Extended translation extension
 * Added wrapping for filters
 *
 * @author juriem
 *
 */
class TranslationExtension extends \Symfony\Bridge\Twig\Extension\TranslationExtension
{
	public function getName()
	{
		return 'extended_translator';
	}

	public function getFunctions(){

		$functions = parent::getFunctions();

		return array_merge($functions, array(
				'trans' => new \Twig_Function_Method($this, 'trans'),
				'transchoice' => new \Twig_Function_Method($this, 'transchoice')
				))
		;
	}
}
