<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\Csv;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Rest\Csv\CsvHandler;
use Sulu\Component\Rest\Csv\ObjectNotSupportedException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testNonListResponse(): void
    {
        $this->expectException(ObjectNotSupportedException::class);
        $object = new \stdClass();

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($object);
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $handler = new CsvHandler($serializer->reveal());
        $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
    }

    public function testListRepresentation(): void
    {
        $listRepresentation = $this->prophesize(PaginatedRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00'), 'enabled' => true],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00'), 'enabled' => false],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($listRepresentation->reveal());
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $handler = new CsvHandler($serializer->reveal());

        \ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            'attachment; filename=contacts.csv'
        );

        $this->assertEquals(
            "id;fullName;birthday;enabled\n1;\"Max Mustermann\";1976-02-01T00:00:00+01:00;1\n2;\"Erika Mustermann\";1964-08-12T00:00:00+01:00;0\n",
            $content
        );
    }

    public function testCollectionRepresentation(): void
    {
        $collectionRepresentation = $this->prophesize(CollectionRepresentation::class);
        $collectionRepresentation->getRel()->willReturn('contacts');
        $collectionRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00'), 'enabled' => true],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00'), 'enabled' => false],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($collectionRepresentation->reveal());
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $handler = new CsvHandler($serializer->reveal());

        \ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            'attachment; filename=contacts.csv'
        );

        $this->assertEquals(
            "id;fullName;birthday;enabled\n1;\"Max Mustermann\";1976-02-01T00:00:00+01:00;1\n2;\"Erika Mustermann\";1964-08-12T00:00:00+01:00;0\n",
            $content
        );
    }

    public function testListRepresentationDifferentConfig(): void
    {
        $listRepresentation = $this->prophesize(PaginatedRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'birthday' => new \DateTime('1976-02-01T00:00:00+01:00')],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'birthday' => new \DateTime('1964-08-12T00:00:00+01:00')],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($listRepresentation->reveal());
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(',');
        $request->get('enclosure', '"')->willReturn('\'');
        $request->get('escape', '\\')->willReturn('"');
        $request->get('newLine', '\\n')->willReturn('\\r\\n');

        $handler = new CsvHandler($serializer->reveal());

        \ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            'attachment; filename=contacts.csv'
        );

        $this->assertEquals(
            "id,fullName,birthday\r\n1,'Max Mustermann',1976-02-01T00:00:00+01:00\r\n2,'Erika Mustermann',1964-08-12T00:00:00+01:00\r\n",
            $content
        );
    }

    public function testListRepresentationWithArray(): void
    {
        $listRepresentation = $this->prophesize(PaginatedRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn(
            [
                ['id' => 1, 'fullName' => 'Max Mustermann', 'properties' => ['test' => 1]],
                ['id' => 2, 'fullName' => 'Erika Mustermann', 'properties' => ['test' => 2]],
            ]
        );

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($listRepresentation->reveal());
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $handler = new CsvHandler($serializer->reveal());

        \ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            'attachment; filename=contacts.csv'
        );

        $this->assertEquals(
            "id;fullName;properties\n1;\"Max Mustermann\";\"{\"\"test\"\":1}\"\n2;\"Erika Mustermann\";\"{\"\"test\"\":2}\"\n",
            $content
        );
    }

    public function testListRepresentationEmpty(): void
    {
        $listRepresentation = $this->prophesize(PaginatedRepresentation::class);
        $listRepresentation->getRel()->willReturn('contacts');
        $listRepresentation->getData()->willReturn([]);

        $viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $view = new View($listRepresentation->reveal());
        $request = $this->prophesize(Request::class);
        $serializer = $this->prophesize(ArraySerializerInterface::class);
        $format = 'csv';

        $request->get('delimiter', ';')->willReturn(';');
        $request->get('enclosure', '"')->willReturn('"');
        $request->get('escape', '\\')->willReturn('\\');
        $request->get('newLine', '\\n')->willReturn('\\n');

        $handler = new CsvHandler($serializer->reveal());

        \ob_start();
        $response = $handler->createResponse($viewHandler->reveal(), $view, $request->reveal(), $format);
        $response->send();
        $content = \ob_get_contents();
        \ob_end_clean();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals($response->headers->get('Content-Type'), 'text/csv');
        $this->assertEquals(
            \str_replace('"', '', $response->headers->get('Content-Disposition')),
            'attachment; filename=contacts.csv'
        );

        $this->assertEquals('', $content);
    }
}
