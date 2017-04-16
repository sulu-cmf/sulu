<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * FileVersion
 */
class FileVersion
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $version;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var string
     */
    private $storageOptions;

    /**
     * @var integer
     */
    private $downloadCounter = 0;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contentLanguages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $publishLanguages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $meta;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\File
     * @Exclude
     */
    private $file;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $tags;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $creator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contentLanguages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->publishLanguages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return FileVersion
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return FileVersion
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set size
     *
     * @param integer $size
     * @return FileVersion
     */
    public function setSize($size)
    {
        $this->size = $size;
    
        return $this;
    }

    /**
     * Get size
     *
     * @return integer 
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     * @return FileVersion
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set storageOptions
     *
     * @param string $storageOptions
     * @return FileVersion
     */
    public function setStorageOptions($storageOptions)
    {
        $this->storageOptions = $storageOptions;
    
        return $this;
    }

    /**
     * Get storageOptions
     *
     * @return string 
     */
    public function getStorageOptions()
    {
        return $this->storageOptions;
    }

    /**
     * Set downloadCounter
     *
     * @param integer $downloadCounter
     * @return FileVersion
     */
    public function setDownloadCounter($downloadCounter)
    {
        $this->downloadCounter = $downloadCounter;

        return $this;
    }

    /**
     * Get downloadCounter
     *
     * @return integer
     */
    public function getDownloadCounter()
    {
        return $this->downloadCounter;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return FileVersion
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return FileVersion
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    
        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add contentLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $contentLanguages
     * @return FileVersion
     */
    public function addContentLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages[] = $contentLanguages;
    
        return $this;
    }

    /**
     * Remove contentLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $contentLanguages
     */
    public function removeContentLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage $contentLanguages)
    {
        $this->contentLanguages->removeElement($contentLanguages);
    }

    /**
     * Get contentLanguages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getContentLanguages()
    {
        return $this->contentLanguages;
    }

    /**
     * Add publishLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $publishLanguages
     * @return FileVersion
     */
    public function addPublishLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages[] = $publishLanguages;
    
        return $this;
    }

    /**
     * Remove publishLanguages
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $publishLanguages
     */
    public function removePublishLanguage(\Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage $publishLanguages)
    {
        $this->publishLanguages->removeElement($publishLanguages);
    }

    /**
     * Get publishLanguages
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPublishLanguages()
    {
        return $this->publishLanguages;
    }

    /**
     * Add meta
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionMeta $meta
     * @return FileVersion
     */
    public function addMeta(\Sulu\Bundle\MediaBundle\Entity\FileVersionMeta $meta)
    {
        $this->meta[] = $meta;
    
        return $this;
    }

    /**
     * Remove meta
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersionMeta $meta
     */
    public function removeMeta(\Sulu\Bundle\MediaBundle\Entity\FileVersionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Set file
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\File $file
     * @return FileVersion
     */
    public function setFile(\Sulu\Bundle\MediaBundle\Entity\File $file = null)
    {
        $this->file = $file;
    
        return $this;
    }

    /**
     * Get file
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\File 
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Add tags
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     * @return FileVersion
     */
    public function addTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags[] = $tags;
    
        return $this;
    }

    /**
     * Remove tags
     *
     * @param \Sulu\Bundle\TagBundle\Entity\Tag $tags
     */
    public function removeTag(\Sulu\Bundle\TagBundle\Entity\Tag $tags)
    {
        $this->tags->removeElement($tags);
    }

    /**
     * Remove all tags
     */
    public function removeTags()
    {
        $this->tags->clear();
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return FileVersion
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;
    
        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface 
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return FileVersion
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface 
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * don't clone id to create a new entities
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;

            /**
             * @var FileVersionMeta $meta
             */
            foreach ($this->meta as $meta)
            {
                $meta->setId(null);
            }

            /**
             * @var FileVersionContentLanguage $meta
             */
            foreach ($this->contentLanguages as $contentLanguage)
            {
                $contentLanguage->setId(null);
            }

            /**
             * @var FileVersionPublishLanguage $meta
             */
            foreach ($this->publishLanguages as $publishLanguage)
            {
                $publishLanguage->setId(null);
            }
        }
    }
}
