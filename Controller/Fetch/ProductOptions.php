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
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class ProductOptions extends Action
{
    protected $helper;
    protected $catalogProduct;
    protected $_coreRegistry;
    protected $credis;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ProductOptions constructor.
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $coreRegistry
     * @internal param Product $catalogProduct
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Registry $coreRegistry,
        DeploymentConfig $deploymentConfig,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->context = $context;
        $this->productRepository = $productRepository;
        $this->_coreRegistry = $coreRegistry;
        $cacheSetting = $deploymentConfig->get('cache');
        if (isset($cacheSetting['frontend']['default']['backend_options']['server'])) {
            $this->credis = new Credis_Client($cacheSetting['frontend']['default']['backend_options']['server']);
            $this->credis->select(4);
        }
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $productId = $this->_request->getParam('productId');

        echo $this->getProductOptionInfo($productId);
        exit;
    }

    /**
     * @param $productId
     * @return bool|mixed|string
     */
    public function getProductOptionInfo($productId)
    {
        if (!$this->credis) {
            return false;
        }

        $cacheKey = 'LCP_PRODUCT_INFO_' . $this->storeManager->getStore()->getId() . '_' . $productId;
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

        $block = $this->_view->getLayout()->createBlock('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable');

        return $block->getJsonConfig();
    }
}