<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Routing\Enhancers;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Compat\Structure\StructureBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Routing\Enhancers\ContentEnhancer;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class ContentEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public static function enhanceProvider()
    {
        return [
            [
                true,
                null,
            ],
            [
                true,
                true,
            ],
            [
                false,
                true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('enhanceProvider')]
    public function testEnhance($redirect, $target): void
    {
        $webspace = $this->prophesize(Webspace::class);

        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $customUrl->isRedirect()->willReturn($redirect);

        $inspector = $this->prophesize(DocumentInspector::class);
        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $request = $this->prophesize(Request::class);

        $expected = ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal()];
        if ($target && !$redirect) {
            $target = $this->prophesize(PageDocument::class);
            $customUrl->getTargetDocument()->willReturn($target->reveal());

            $structureMetadata = $this->prophesize(StructureMetadata::class);
            $metadata = $this->prophesize(Metadata::class);
            $metadata->getAlias()->willReturn('test');

            $inspector->getStructureMetadata($target->reveal())->willReturn($structureMetadata->reveal());
            $inspector->getMetadata($target->reveal())->willReturn($metadata->reveal());

            $structure = $this->prophesize(StructureBridge::class);
            $structure->setDocument($target->reveal())->shouldBeCalled();
            $structureManager->wrapStructure('test', $structureMetadata)->willReturn($structure->reveal());

            $expected = [
                '_custom_url' => $customUrl->reveal(),
                '_structure' => $structure->reveal(),
                '_webspace' => $webspace->reveal(),
            ];
        } else {
            $customUrl->getTargetDocument()->willReturn(null);
        }

        $enhancer = new ContentEnhancer($inspector->reveal(), $structureManager->reveal());

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal()],
            $request->reveal()
        );

        $this->assertEquals($expected, $defaults);
    }
}
