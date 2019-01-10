<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

/**
 * Provides Interface to load content.
 */
class ContentTwigExtension extends \Twig_Extension implements ContentTwigExtensionInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer,
        LoggerInterface $logger = null
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_content_load', [$this, 'load']),
            new \Twig_SimpleFunction('sulu_content_load_parent', [$this, 'loadParent']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load($uuid)
    {
        if (!$uuid) {
            return;
        }

        try {
            $contentStructure = $this->contentMapper->load(
                $uuid,
                $this->requestAnalyzer->getWebspace()->getKey(),
                $this->requestAnalyzer->getCurrentLocalization()->getLocale()
            );

            return $this->structureResolver->resolve($contentStructure);
        } catch (DocumentNotFoundException $e) {
            $this->logger->error((string) $e);

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadParent($uuid)
    {
        $session = $this->sessionManager->getSession();
        $contentsNode = $this->sessionManager->getContentNode($this->requestAnalyzer->getWebspace()->getKey());
        $node = $session->getNodeByIdentifier($uuid);

        if ($node->getDepth() <= $contentsNode->getDepth()) {
            throw new ParentNotFoundException($uuid);
        }

        return $this->load($node->getParent()->getIdentifier());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_content';
    }
}
