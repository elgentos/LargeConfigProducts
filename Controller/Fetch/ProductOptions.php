<?php

/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 4-1-18
 * Time: 11:20
 */

namespace Elgentos\LargeConfigProducts\Controller\Fetch;

use Credis_Client;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ProductTypeConfigurable;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class ProductOptions extends Action
{
    protected $helper;

    protected $catalogProduct;

    protected $_coreRegistry;

    protected $credis;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /** @var CustomerSession */
    private $customerSession;

    /**
     * ProductOptions constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $coreRegistry
     *
     * @internal param Product $catalogProduct
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->_coreRegistry     = $coreRegistry;
        $this->storeManager      = $storeManager;
        $this->customerSession   = $customerSession;
        $this->credis = new Credis_Client(
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_host') ?? 'localhost',
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_port') ?? 6379,
            null,
            '',
            $scopeConfig->getValue('elgentos_largeconfigproducts/prewarm/redis_db_index') ?? 4
        );
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $productId = $this->_request->getParam('productId');

        echo $this->getProductOptionInfo($productId);
        exit;
    }

    /**
     * @param $productId
     *
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductOptionInfo($productId)
    {
        if (!$this->credis) {
            return false;
        }

        $storeId         = $this->storeManager->getStore()->getId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $cacheKey = 'LCP_PRODUCT_INFO_' . $storeId . '_' . $productId . '_' . $customerGroupId;
        
        if ($this->credis->exists($cacheKey)) {
            return $this->credis->get($cacheKey);
        }

        $product = $this->productRepository->getById($productId);
        if ($product->getId()) {
            $productOptionInfo = $this->getJsonConfig($product);
            $this->credis->set($cacheKey, $productOptionInfo);

            return $productOptionInfo;
        }

        return false;
    }

    /**
     * @param $currentProduct
     *
     * @return mixed
     *
     * See original method at Magento\ConfigurableProduct\Block\Product\View\Type\Configurable::getJsonConfig
     */
    public function getJsonConfig($currentProduct)
    {
        if ($this->_coreRegistry->registry('product')) {
            $this->_coreRegistry->unregister('product');
        }
        $this->_coreRegistry->register('product', $currentProduct);

        /** @var ProductTypeConfigurable $block */
        $block = $this->_view->getLayout()->createBlock(ProductTypeConfigurable::class);

        return $block->getJsonConfig();
    }
}