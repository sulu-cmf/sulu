<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Exception\ParentNotFoundException;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Interface to load content.
 */
class ContentTwigExtension extends AbstractExtension implements ContentTwigExtensionInterface
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
     * @var SecurityCheckerInterface|null
     */
    private $securityChecker;

    /**
     * @var WebspaceManagerInterface|null
     */
    private $webspaceManager;

    /**
     * @var RequestStack|null
     */
    private $requestStack;

    /**
     * @var array{urls?: bool}
     */
    private $enabledTwigAttributes;

    /**
     * @param array{urls?: bool} $enabledTwigAttributes
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        SessionManagerInterface $sessionManager,
        RequestAnalyzerInterface $requestAnalyzer,
        ?LoggerInterface $logger = null,
        $securityChecker = null,
        ?WebspaceManagerInterface $webspaceManager = null,
        ?RequestStack $requestStack = null,
        array $enabledTwigAttributes = [
            'urls' => true,
        ]
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->sessionManager = $sessionManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->logger = $logger ?: new NullLogger();

        if ($securityChecker instanceof RequestStack) {
            @trigger_deprecation('sulu/sulu', '2.2', 'Instantiating the "ContentTwigExtension" without the "$securityChecker" and "$webspaceManager" parameter is deprecated');

            $requestStack = $securityChecker;
            $securityChecker = null;
        }

        $this->securityChecker = $securityChecker;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;

        if (null === $this->requestStack) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Instantiating the "ContentTwigExtension" without the "$requestStack" parameter is deprecated');
        }

        $this->enabledTwigAttributes = $enabledTwigAttributes;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_load', [$this, 'load']),
            new TwigFunction('sulu_content_load_parent', [$this, 'loadParent']),
        ];
    }

    public function load($uuid, ?array $properties = null)
    {
        if (!$uuid) {
            return;
        }

        $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();

        try {
            $contentStructure = $this->contentMapper->load(
                $uuid,
                $this->requestAnalyzer->getWebspace()->getKey(),
                $locale
            );
        } catch (DocumentNotFoundException $e) {
            $this->logger->error((string) $e);

            return;
        }

        $document = $contentStructure->getDocument();

        if ($this->securityChecker && $document instanceof WebspaceBehavior && $document instanceof SecurityBehavior) {
            $targetWebspace = $this->webspaceManager->findWebspaceByKey($contentStructure->getWebspaceKey());
            $security = $targetWebspace->getSecurity();
            $system = $security ? $security->getSystem() : null;

            if ($targetWebspace->hasWebsiteSecurity()
                && !$this->securityChecker->hasPermission(
                    new SecurityCondition(
                        PageAdmin::SECURITY_CONTEXT_PREFIX . $contentStructure->getWebspaceKey(),
                        $locale,
                        SecurityBehavior::class,
                        $uuid,
                        $system
                    ),
                    PermissionTypes::VIEW
                )
            ) {
                return null;
            }
        }

        if (null === $properties) {
            @trigger_deprecation('sulu/sulu', '2.3', 'Calling the "sulu_content_load" function without a properties parameter is deprecated and has a negative impact on performance.');

            return $this->resolveStructure($contentStructure);
        }

        return $this->resolveProperties($contentStructure, $properties);
    }

    public function loadParent($uuid, ?array $properties = null)
    {
        $session = $this->sessionManager->getSession();
        $contentsNode = $this->sessionManager->getContentNode($this->requestAnalyzer->getWebspace()->getKey());
        $node = $session->getNodeByIdentifier($uuid);

        if ($node->getDepth() <= $contentsNode->getDepth()) {
            throw new ParentNotFoundException($uuid);
        }

        return $this->load($node->getParent()->getIdentifier(), $properties);
    }

    private function resolveStructure(
        StructureInterface $structure,
        bool $loadExcerpt = true,
        ?array $includedProperties = null
    ) {
        if (null === $this->requestStack) {
            $structureData = $this->structureResolver->resolve($structure, $loadExcerpt, $includedProperties);
        } else {
            $currentRequest = $this->requestStack->getCurrentRequest();

            // This sets query parameters, request parameters and files to an empty array
            $subRequest = $currentRequest->duplicate([], [], null, null, []);
            $this->requestStack->push($subRequest);

            try {
                $structureData = $this->structureResolver->resolve($structure, $loadExcerpt, $includedProperties);
            } finally {
                $this->requestStack->pop();
            }
        }

        if ($this->enabledTwigAttributes['urls'] ?? true) {
            @trigger_deprecation('sulu/sulu', '2.2', 'Enabling the "urls" parameter is deprecated');
        } else {
            unset($structureData['urls']);
        }

        return $structureData;
    }

    private function resolveProperties(StructureInterface $contentStructure, array $properties): array
    {
        $contentProperties = [];
        $extensionProperties = [];

        foreach ($properties as $targetProperty => $sourceProperty) {
            if (!\is_string($targetProperty)) {
                $targetProperty = $sourceProperty;
            }

            if (!\strpos($sourceProperty, '.')) {
                $contentProperties[$targetProperty] = $sourceProperty;
            } else {
                $extensionProperties[$targetProperty] = $sourceProperty;
            }
        }

        $resolvedStructure = $this->resolveStructure(
            $contentStructure,
            !empty($extensionProperties),
            \array_values($contentProperties)
        );

        foreach ($contentProperties as $targetProperty => $sourceProperty) {
            if (isset($resolvedStructure['content'][$sourceProperty]) && $sourceProperty !== $targetProperty) {
                $resolvedStructure['content'][$targetProperty] = $resolvedStructure['content'][$sourceProperty];
                $resolvedStructure['view'][$targetProperty] = $resolvedStructure['view'][$sourceProperty] ?? [];

                unset($resolvedStructure['content'][$sourceProperty]);
                unset($resolvedStructure['view'][$sourceProperty]);
            }
        }

        foreach ($extensionProperties as $targetProperty => $sourceProperty) {
            [$extensionName, $propertyName] = \explode('.', $sourceProperty);
            $propertyValue = $resolvedStructure['extension'][$extensionName][$propertyName];

            $resolvedStructure['content'][$targetProperty] = $propertyValue;
            $resolvedStructure['view'][$targetProperty] = [];
        }
        unset($resolvedStructure['extension']);

        return $resolvedStructure;
    }
}
