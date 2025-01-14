<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DataCollector;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class SuluCollector extends DataCollector
{
    public function __construct(
        private string $kernelEnvironment = 'dev'
    ) {
    }

    public function data($key)
    {
        return $this->data[$key] ?? null;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        if (!$request->attributes->has('_sulu')) {
            return;
        }

        /** @var RequestAttributes $requestAttributes */
        $requestAttributes = $request->attributes->get('_sulu');

        /** @var ?Webspace $webspace */
        $webspace = $requestAttributes->getAttribute('webspace');
        /** @var ?Portal $portal */
        $portal = $requestAttributes->getAttribute('portal');
        $segment = $requestAttributes->getAttribute('segment');

        $this->data['match_type'] = $requestAttributes->getAttribute('matchType');
        $this->data['redirect'] = $requestAttributes->getAttribute('redirect');
        $this->data['portal_url'] = $requestAttributes->getAttribute('portalUrl');
        $this->data['segment'] = $requestAttributes->getAttribute('segment');

        if ($webspace) {
            $this->data['webspace'] = $webspace->toArray();
            unset($this->data['webspace']['portals']);
            $this->flattenLocalization($this->data['webspace']['localizations']);
        }

        if ($portal) {
            $this->data['portal'] = $portal->toArray();
            $this->data['portal']['environments'] = \array_combine(
                \array_column($this->data['portal']['environments'] ?? [], 'type'),
                $this->data['portal']['environments'] ?? [],
            );
            $this->flattenLocalization($this->data['portal']['localizations']);
            $this->data['environment'] = $portal->getEnvironment($this->kernelEnvironment);
        }

        if ($segment) {
            $this->data['segment'] = $segment->toArray();
        }

        $this->data['localization'] = $requestAttributes->getAttribute('localization');
        $this->data['resource_locator'] = $requestAttributes->getAttribute('resourceLocator');
        $this->data['resource_locator_prefix'] = $requestAttributes->getAttribute('resourceLocatorPrefix');

        $structure = null;
        if ($request->attributes->has('_route_params')) {
            $params = $request->attributes->get('_route_params');
            if (isset($params['structure'])) {
                /** @var StructureInterface $structureObject */
                $structureObject = $params['structure'];

                $structure = [
                    'id' => $structureObject->getUuid(),
                    'objectClass' => $structureObject::class,
                    'path' => $structureObject->getPath(),
                    'nodeType' => $structureObject->getNodeType(),
                    'internal' => $structureObject->getInternal(),
                    'nodeState' => $structureObject->getNodeState(),
                    'published' => $structureObject->getPublished(),
                    'publishedState' => $structureObject->getPublishedState(),
                    'navContexts' => $structureObject->getNavContexts(),
                    'shadowLocales' => $structureObject->getShadowLocales(),
                    'contentLocales' => $structureObject->getContentLocales(),
                    'shadowOn' => $structureObject->getIsShadow(),
                    'shadowBaseLanguage' => $structureObject->getShadowBaseLanguage(),
                    'template' => $structureObject->getKey(),
                    'originTemplate' => $structureObject->getOriginTemplate(),
                    'hasSub' => $structureObject->getHasChildren(),
                    'creator' => $structureObject->getCreator(),
                    'changer' => $structureObject->getChanger(),
                    'created' => $structureObject->getCreated(),
                    'changed' => $structureObject->getChanged(),
                ];
            }
        }
        $this->data['structure'] = $structure;
    }

    /**
     * @param array<array{language: string, default: bool}>|null $localizations
     */
    private function flattenLocalization(?array &$localizations): void
    {
        if (null === $localizations) {
            return;
        }
        foreach ($localizations as &$localization) {
            $localization = (string) $localization['language'] . ($localization['default'] ? ' (default)' : '');
        }
    }

    public function getName()
    {
        return 'sulu';
    }

    public function reset()
    {
        $this->data = [];
    }
}
