<?php
/**
 * Created by PhpStorm.
 * User: NgocNH
 * Date: 7/13/2018
 * Time: 11:05 AM
 */

namespace SmartOSC\Helloworld\Block;


class Display extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    )
    {
        parent::__construct($context);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout(); // TODO: Change the autogenerated stub
    }
}
