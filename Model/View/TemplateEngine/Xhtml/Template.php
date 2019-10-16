<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 17-1-18
 * Time: 21:44.
 */

namespace Elgentos\LargeConfigProducts\Model\View\TemplateEngine\Xhtml;

class Template extends \Magento\Framework\View\TemplateEngine\Xhtml\Template
{
    /**
     * Fix for CDATA section is too big error in the backend
     * See https://github.com/magento/magento2/issues/7658
     * and https://github.com/magento/magento2/issues/8084.
     *
     * @param string $content
     *
     * @return void
     */
    public function append($content)
    {
        $target = $this->templateNode->ownerDocument;

        $source = new \DOMDocument();
        $source->loadXml($content, LIBXML_PARSEHUGE);

        $this->templateNode->appendChild(
            $target->importNode($source->documentElement, true)
        );
    }
}
