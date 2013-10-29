<?php
// Language.php
/**
 * Created by JetBrains PhpStorm.
 * User: juriem
 * Date: 26/10/13
 * Time: 11:50
 * To change this template use File | Settings | File Templates.
 */

namespace Gizlab\Bundle\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Language
 * @package Gizlab\Bundle\TranslationBundle\Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="ext_gizlab_languages")
 */
class Language
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=2)
     * @var string
     */
    private $id;

    /**
     *
     * @ORM\Column(type="boolean", name="is_default", options={"default"=0})
     * @var bool
     */
    private $isDefault = false;

    /**
     * @ORM\Column(type="string", length=5, name="locale_name")
     * @var string
     */
    private $locale;

    /**
     *
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $label;

    /*
     * Auto generated
     */

    /**
     * Set id
     *
     * @param string $id
     * @return Language
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return Language
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    
        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Language
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    
        return $this;
    }

    /**
     * Get locale
     *
     * @return string 
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return Language
     */
    public function setLabel($label)
    {
        $this->label = $label;
    
        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }
}