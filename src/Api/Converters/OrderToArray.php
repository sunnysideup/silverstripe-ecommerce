<?php

namespace Sunnysideup\Ecommerce\Api\Converters;

use Sunnysideup\Ecommerce\Api\OrderConverter;

/**
 * todo: turn into registered object and let the objects return it.
 * e.g. give me all classes that implement interface OrderToArrayReady
 * and then just run it.
 */
class OrderToArray extends OrderConverter
{
    public function convert(): array
    {
        $currency = $this->order->CurrencyUsed();
        $billing = $this->order->BillingAddress();
        $shipping = $billing;
        $items = $this->order->OrderItems();
        $modifiers = $this->order->Modifiers();
        if ($this->order->IsSeparateShippingAddress()) {
            $shipping = $this->order->ShippingAddress();
        }
        $array = [
            'totalAmount' => [
                'amount' => $this->order->Total,
                'currency' => $currency->Code,
            ],
            'consumer' => [
                'phoneNumber' => $billing->Phone,
                'givenNames' => $billing->FirstName,
                'surname' => $billing->Surname,
                'email' => $billing->Email,
            ],
            'billing' => [
                'name' => $billing->FirstName . ' ' . $billing->Surname,
                'line1' => $billing->Address . ' ' . $billing->Address2,
                'state' => $billing->City,
                'postcode' => $billing->PostalCode,
                'countryCode' => $billing->Country,
                'phoneNumber' => $billing->Phone,
            ],
            'shipping' => [
                'name' => $shipping->FirstName . ' ' . $shipping->Surname,
                'line1' => $shipping->Address . ' ' . $shipping->Address2,
                'state' => $shipping->City,
                'postcode' => $shipping->PostalCode,
                'countryCode' => $shipping->Country,
                'phoneNumber' => $shipping->Phone,
            ],
            'merchantReference' => $this->order->ID,
            'items' => [],
            'discounts' => [],
            'taxAmount' => [
                'amount' => 0,
                'currency' => 0,
            ],
            'shippingAmount' => [
                'amount' => 0,
                'currency' => 0,
            ],
        ];
        foreach ($items as $item) {
            $array['items'][] = [
                'name' => $item->getTitle(),
                'sku' => $item->getInternalItemID(),
                'quantity' => $item->Quantity,
                'price' => [
                    'amount' => $item->UnitPriceAsMoney()->Amount,
                    'currency' => $item->UnitPriceAsMoney()->Currency,
                ],
            ];
        }
        foreach ($modifiers as $modifier) {
            switch ($modifier->Type) {
                case 'Discount':
                    $array['discounts'][] = [
                        'displayName' => 'discount',
                        'amount' => $modifier->CalculatedTotal,
                        'currency' => $currency->Code,
                    ];

                    break;
                case 'Delivery':
                    $array['shippingAmount'] = [
                        'amount' => $array['shippingAmount']['amount'] + $modifier->CalculatedTotal,
                        'currency' => $currency->Code,
                    ];

                    break;
                case 'Tax':
                    $array['taxAmount'] = [
                        'amount' => $array['taxAmount']['amount'] + $modifier->CalculatedTotal,
                        'currency' => $currency->Code,
                    ];

                    break;
            }
        }

        return $array;
    }
}
