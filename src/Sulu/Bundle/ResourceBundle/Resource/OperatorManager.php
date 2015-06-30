<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource;

use Sulu\Bundle\ResourceBundle\Api\Operator;
use Sulu\Bundle\ResourceBundle\Entity\OperatorRepositoryInterface;

class OperatorManager implements OperatorManagerInterface
{
    /**
     * @var OperatorRepositoryInterface
     */
    protected $operatorRepo;

    function __construct(OperatorRepositoryInterface $operatorRepo)
    {
        $this->operatorRepo = $operatorRepo;
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByLocale($locale)
    {
        $operators = $this->operatorRepo->findAllByLocale($locale);

        if ($operators) {
            array_walk(
                $operators,
                function (&$operator) use ($locale) {
                    $operator = new Operator($operator, $locale);
                }
            );
        }

        return $operators;
    }
}
