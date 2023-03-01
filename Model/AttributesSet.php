<?php
/*
 * Copyright © Ignacio Muñoz © All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Iamunozz\EavRemover\Model;

use Magento\Catalog\Model\Product\AttributeSet\Options as AttributeSetOptions;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;


class AttributesSet
{

    /**
     * @var AttributeSetOptions
     */
    protected $attributeSet;

    /**
     * @var ProductAttributeManagementInterface
     */
    private $_productAttributeManagement;

    /**
     * @var array
     */
    protected $attributeSetIds = [];

    /**
     * AttributesSet constructor.
     *
     * @param AttributeSetOptions $attributeSet
     * @param ProductAttributeManagementInterface $productAttributeManagement
     */
    public function __construct(
        AttributeSetOptions $attributeSet,
        ProductAttributeManagementInterface $productAttributeManagement
    ) {
        $this->attributeSet = $attributeSet;
        $this->_productAttributeManagement = $productAttributeManagement;
    }

    /**
     * Retrieve all attribute set ids
     *
     * @return array
     */
    public function getAllAttributeSetIds(): array
    {
        foreach ($this->attributeSet->toOptionArray() as $attributeSet) {
            $this->attributeSetIds[] = $attributeSet['value'];
        }
        return $this->attributeSetIds;
    }

    /**
     *
     * @param array $attributeSetIds
     * @param string|int $attributeCode
     * @return bool
     */
    public function existAttributeOnSet($attributeSetIds, $attributeCode): bool
    {
        try {
            foreach ($attributeSetIds as $attributeSetId) {
                $attributes = $this->_productAttributeManagement->getAttributes($attributeSetId);
                foreach ($attributes as $attribute) {
                    /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
                    if ($attributeCode === $attribute->getAttributeCode()) {
                        return true;
                    }
                }
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return false;
    }
}
