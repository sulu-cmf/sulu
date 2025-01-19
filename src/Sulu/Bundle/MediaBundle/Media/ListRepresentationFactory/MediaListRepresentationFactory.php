<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ListRepresentationFactory;

use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\ListBuilder\RepresentationInterface;

class MediaListRepresentationFactory
{
    public function __construct(
        private MediaManagerInterface $mediaManager,
        private FormatManagerInterface $formatManager,
    ) {
    }

    public function getListRepresentation(
        DoctrineListBuilder $listBuilder,
        string $locale,
        string $rel,
        string $route,
        array $parameters
    ): RepresentationInterface {
        $listBuilder->setParameter('locale', $locale);
        $listResponse = $listBuilder->execute();

        for ($i = 0, $length = \count($listResponse); $i < $length; ++$i) {
            $format = $this->formatManager->getFormats(
                $listResponse[$i]['previewImageId'] ?? $listResponse[$i]['id'],
                $listResponse[$i]['previewImageName'] ?? $listResponse[$i]['name'],
                $listResponse[$i]['previewImageVersion'] ?? $listResponse[$i]['version'],
                $listResponse[$i]['previewImageSubVersion'] ?? $listResponse[$i]['subVersion'],
                $listResponse[$i]['previewImageMimeType'] ?? $listResponse[$i]['mimeType']
            );

            if (0 < \count($format)) {
                $listResponse[$i]['thumbnails'] = $format;
            }

            $listResponse[$i]['url'] = $this->mediaManager->getUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );

            $listResponse[$i]['adminUrl'] = $this->mediaManager->getAdminUrl(
                $listResponse[$i]['id'],
                $listResponse[$i]['name'],
                $listResponse[$i]['version']
            );

            if ($locale !== $listResponse[$i]['locale']) {
                $listResponse[$i]['ghostLocale'] = $listResponse[$i]['locale'];
            }
        }

        $ids = $listBuilder->getIds();
        if (null != $ids) {
            $result = [];
            foreach ($listResponse as $item) {
                $result[\array_search($item['id'], $ids)] = $item;
            }
            \ksort($result);
            $listResponse = \array_values($result);
        }

        return new PaginatedRepresentation(
            $listResponse,
            $rel,
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );
    }
}
