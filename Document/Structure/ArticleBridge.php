<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ArticleBundle\Document\Structure;

use Sulu\Component\Content\Compat\Structure\StructureBridge;

/**
 * Own structure bridge for articles.
 */
class ArticleBridge extends StructureBridge implements RoutableStructureInterface
{
    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var string
     */
    private $uuid;

    public function getView(): string
    {
        /** @var string */
        return $this->structure->getView();
    }

    public function getController(): string
    {
        /** @var string */
        return $this->structure->getController();
    }

    /**
     * @return array{type: string, value: string}
     */
    public function getCacheLifeTime(): array
    {
        /** @var array{type: string, value: string} */
        return $this->structure->getCacheLifetime();
    }

    public function getUuid()
    {
        // is set for structure loaded with document from document-manager
        // is not set when using structure with view-document
        if ($this->document) {
            return parent::getUuid();
        }

        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Will be called by SuluCollector to collect profiler data.
     */
    public function getNavContexts()
    {
        return null;
    }

    public function getEnabledShadowLanguages(): array
    {
        return $this->inspector->getShadowLocales($this->getDocument());
    }

    public function getConcreteLanguages(): array
    {
        return $this->inspector->getConcreteLocales($this->getDocument());
    }

    /**
     * @return mixed
     *
     * Will be called by SuluCollector to collect profiler data
     */
    public function getOriginTemplate()
    {
        return null;
    }

    public function getExt()
    {
        return $this->document->getExtensionsData();
    }

    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey($webspace)
    {
        $this->webspaceKey = $webspace;
    }

    public function getLanguageCode()
    {
        if (!$this->document) {
            return $this->locale;
        }

        // return original locale for shadow or ghost pages
        if ($this->getIsShadow() || ($this->getType() && 'ghost' === $this->getType()->getName())) {
            return $this->inspector->getOriginalLocale($this->getDocument());
        }

        return parent::getLanguageCode();
    }
}
