<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\DocumentRegistry;

class DocumentRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var object
     */
    private $document;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    public function setUp(): void
    {
        $this->registry = new DocumentRegistry('de');
        $this->node = $this->prophesize(NodeInterface::class);
        $this->node->getIdentifier()->willReturn('1234');
        $this->document = new \stdClass();
    }

    /**
     * It should register a document and its associated PHPCR node.
     */
    public function testRegisterDocument(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertTrue($this->registry->hasDocument($this->document));
        $this->assertTrue($this->registry->hasNode($this->node->reveal(), 'fr'));
    }

    /**
     * It should deregister a given document.
     */
    #[\PHPUnit\Framework\Attributes\Depends('testRegisterDocument')]
    public function testDeregisterDocument(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->registry->deregisterDocument($this->document);
        $this->assertFalse($this->registry->hasDocument($this->document));
        $this->assertFalse($this->registry->hasNode($this->node->reveal(), 'fr'));
    }

    /**
     * It should throw an exception when an unregistered document is deregistered.
     */
    public function testDeregisterDocumentUnknown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->registry->deregisterDocument($this->document);
    }

    /**
     * It should return the PHPCR node for a registered document.
     */
    #[\PHPUnit\Framework\Attributes\Depends('testRegisterDocument')]
    public function testGetNodeForDocument(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertSame(
            $this->node->reveal(),
            $this->registry->getNodeForDocument($this->document)
        );
    }

    /**
     * Throw an exception if an attempt is made to re-register a document.
     */
    public function testDifferentInstanceSameNode(): void
    {
        $this->expectExceptionMessage('is already registered');
        $this->expectException(\RuntimeException::class);
        $this->node->getPath()->willReturn('/path/to');
        $this->registry->registerDocument(new \stdClass(), $this->node->reveal(), 'fr');
        $this->registry->registerDocument(new \stdClass(), $this->node->reveal(), 'fr');
    }

    /**
     * It should throw an exception if an unregistered document is passed to get node for document.
     */
    public function testGetNodeForDocumentUnknown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->registry->getNodeForDocument($this->document);
    }

    /**
     * It should return a document for a mangaed node.
     */
    #[\PHPUnit\Framework\Attributes\Depends('testRegisterDocument')]
    public function testGetDocumentForNode(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $document = $this->registry->getDocumentForNode($this->node->reveal(), 'fr');
        $this->assertSame($this->document, $document);
    }

    /**
     * It should provide a method to clear the registry.
     */
    public function testClear(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertTrue($this->registry->hasDocument($this->document));
        $this->registry->clear();
        $this->assertFalse($this->registry->hasDocument($this->document));
    }

    /**
     * It should be able to determine the locale of a document.
     */
    public function testGetLocaleForDocument(): void
    {
        $this->registry->registerDocument($this->document, $this->node->reveal(), 'fr');
        $this->assertEquals('fr', $this->registry->getLocaleForDocument($this->document));
    }

    /**
     * It should return the default locale.
     */
    public function testGetDefaultLocale(): void
    {
        $this->assertEquals('de', $this->registry->getDefaultLocale());
    }
}
