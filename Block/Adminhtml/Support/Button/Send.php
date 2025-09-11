<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

declare(strict_types=1);

namespace Klarna\Support\Block\Adminhtml\Support\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * @internal
 */
class Send implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     * @codeCoverageIgnore
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label'      => __('Send'),
            'class'      => 'action-secondary',
            'sort_order' => 20,
            'on_click'   => '',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'support_form.support_form',
                                'actionName' => 'save',
                                'params' => [
                                    false
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }
}
