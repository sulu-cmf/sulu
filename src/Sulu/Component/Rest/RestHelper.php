<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;

/**
 * Defines some common REST functionalities.
 */
class RestHelper implements RestHelperInterface
{
    use RelationTrait;

    /**
     * @var ListRestHelperInterface
     */
    private $listRestHelper;

    public function __construct(ListRestHelper $listRestHelper)
    {
        $this->listRestHelper = $listRestHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors)
    {
        $listBuilder->limit($this->listRestHelper->getLimit())->setCurrentPage($this->listRestHelper->getPage());
        $listBuilder->setFieldDescriptors($fieldDescriptors);

        $fields = $this->listRestHelper->getFields();
        if (null != $fields) {
            foreach ($fields as $field) {
                if (!array_key_exists($field, $fieldDescriptors)) {
                    continue;
                }

                $listBuilder->addSelectField($fieldDescriptors[$field]);
            }
        } else {
            $listBuilder->setSelectFields($fieldDescriptors);
        }

        $search = $this->listRestHelper->getSearchPattern();
        if ($search) {
            $searchFields = $this->listRestHelper->getSearchFields();

            if (!$searchFields) {
                $searchFields = $this->getDefaultSearchFields($fieldDescriptors);
            }

            foreach ($searchFields as $searchField) {
                $fieldDescriptor = $fieldDescriptors[$searchField];

                if (FieldDescriptorInterface::SEARCHABILITY_NEVER === $fieldDescriptor->getSearchability()) {
                    continue;
                }

                $listBuilder->addSearchField($fieldDescriptor);
            }

            $listBuilder->search($this->listRestHelper->getSearchPattern());
        }

        $sortBy = $this->listRestHelper->getSortColumn();
        if (null != $sortBy) {
            $listBuilder->sort($fieldDescriptors[$sortBy], $this->listRestHelper->getSortOrder());
        }
    }

    /**
     * @param FieldDescriptor[] $fieldDescriptors
     *
     * @return string[]
     */
    private function getDefaultSearchFields(array $fieldDescriptors): array
    {
        $defaultSearchFields = [];
        foreach ($fieldDescriptors as $fieldDescriptor) {
            if (FieldDescriptorInterface::SEARCHABILITY_YES === $fieldDescriptor->getSearchability()) {
                $defaultSearchFields[] = $fieldDescriptor->getName();
            }
        }

        return $defaultSearchFields;
    }
}
