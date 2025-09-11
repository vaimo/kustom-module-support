<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Support\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @internal
 */
class Products implements OptionSourceInterface
{
    /**
     * Returns product options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value'         => "KP",
                "label"         => "Klarna Payment",
                "__disableTmpl" => true,
            ], [
                'value'         => "OM",
                "label"         => "Order Management",
                "__disableTmpl" => true,
            ], [
                'value'         => "OSM",
                "label"         => "On-Site Messaging",
                "__disableTmpl" => true,
            ], [
                'value'         => "GraphQL",
                "label"         => "GraphQL",
                "__disableTmpl" => true,
            ], [
                'value'         => "KCO",
                "label"         => "Klarna Checkout",
                "__disableTmpl" => true,
            ], [
                'value'         => "KSA",
                "label"         => "Klarna Shipping Assistant",
                "__disableTmpl" => true,
            ], [
                'value'         => "SIWK",
                "label"         => "Sign in with Klarna",
                "__disableTmpl" => true,
            ], [
                'value'         => "KEC",
                "label"         => "Klarna Express Checkout",
                "__disableTmpl" => true,
            ]
        ];
    }
}
