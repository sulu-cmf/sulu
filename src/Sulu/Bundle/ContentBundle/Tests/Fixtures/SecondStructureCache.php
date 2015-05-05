<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Fixtures;

use Sulu\Component\Content\Property;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Section\SectionProperty;
use Sulu\Component\Content\Structure\Page;

/**
 * This structure cache has more search features than DefaultStructureCache
 */
class SecondStructureCache extends Page
{
    public function __construct()
    {
        parent::__construct('default', 'ClientWebsiteBundle:templates:default.html.twig', 'SuluWebsiteBundle:Default:index', '2400');

        $prop1 = new Property(
            'title',
            array(
                'title' =>             array(
                'de' => 'Titel',
                'en' => 'Title',
            )
,
            )
,
            'text_line',
            true,
            true,
            1,
            1,
            array(
            ),
            array(
                'sulu.rlp.part' => new PropertyTag('sulu.rlp.part', 1),
                'sulu.search.field' => new PropertyTag('sulu.search.field', 1, array('type' => 'string', 'role' => 'title')),
            ),
            ''
        );
        $this->addChild($prop1);

        $prop1 = new Property(
            'url',
            array(
                'title' =>             array(
                'de' => 'Adresse',
                'en' => 'Resourcelocator',
            )
,
            )
,
            'resource_locator',
            true,
            true,
            1,
            1,
            array(
            ),
            array(
                'sulu.rlp' => new PropertyTag('sulu.rlp', 1),
            ),
            ''
        );
        $this->addChild($prop1);

        $prop1 = new Property(
            'links',
            array(
                'title' =>             array(
                'de' => 'Interne Links',
                'en' => 'Internal links',
            )
,
            )
,
            'internal_links',
            false,
            true,
            1,
            1,
            array(
            ),
            array(
            ),
            ''
        );
        $this->addChild($prop1);

        $prop1 = new Property(
            'images',
            array(
                'title' =>             array(
                'de' => 'Bilder',
                'en' => 'Images',
            )
,
            )
,
            'media_selection',
            false,
            true,
            1,
            1,
            array(
            )
        );
        $this->addChild($prop1);

        // section content
                $section1 = new SectionProperty(
            'content',
                        array(
                'title' =>             array(
                'de' => 'Inhalt',
                'en' => 'Content',
            )
,
                'info_text' =>             array(
                'de' => 'Bereich für den Inhalt',
                'en' => 'Content Section',
            )
,
            )
,
            ''
        );
        $prop1 = new Property(
            'article',
            array(
                'title' =>             array(
                'de' => 'Artikel',
                'en' => 'Article',
            )
,
            )
,
            'text_editor',
            false,
            true,
            1,
            1,
            array(
                'godMode' => 'true',
            ),
            array(
                'sulu.search.field' => new PropertyTag('sulu.search.field', 1, array('type' => 'string', 'role' => 'description')),
            ),
            ''
        );
        $section1->addChild($prop1);

        $this->addChild($section1);

    }
}
