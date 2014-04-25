<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template\Dumper;

/**
 * Class PHPTemplateDumper
 * @package Sulu\Component\Content\Template\Dumper
 */
class PHPTemplateDumper
{
    /**
     * @var
     */
    private $twig;

    /**
     * @param string $path path to twig templates
     * @param boolean $debug
     */
    function __construct($path, $debug)
    {
        if (strpos($path, '/') !== 0) {
            $path = __DIR__ . '/' . $path;
        }
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem($path), array('debug' => $debug));

        if ($debug) {
            $this->twig->addExtension(new \Twig_Extension_Debug());
        }
    }


    /**
     * Creates a new class with the data from the given collection
     * @param array $results
     * @param array $options
     * @return string
     */
    public function dump($results, $options = array())
    {
        return $this->twig->render(
            'StructureClass.php.twig',
            array(
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'content' => $results
            )
        );
    }
}
