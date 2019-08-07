<?php

namespace Elgentos\LargeConfigProducts\Model;

/**
 * Class StoreIdStatic
 * @package Elgentos\LargeConfigProducts\Model
 *
 *
 * The currentStoreId that is being set in the emulation in PrewarmerCommand is somehow lost in the call stack.
 * This value object stores the store ID in order to re-set the current store so the translated
 * attribute option labels are retrieved correctly in the AttributeOptionProviderPlugin.
 *
 */
class StoreIdStatic
{
    static protected $storeId;

    /**
     * @param $storeId
     */
    public function setStoreId($storeId) {
        self::$storeId = $storeId;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return self::$storeId;
    }

}