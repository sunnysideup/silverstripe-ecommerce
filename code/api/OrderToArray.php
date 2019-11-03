<?php


class OrderToArray extends Object
{

    protected $order = null;

    public function __construct(Order $order)
    {
        parent::__construct();
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function toArray() :array
    {
        $currency = $order->CurrencyUsed();
        $billing = $order->BillingAddress();
        $shipping = $billing;
        $items = $order->getOrderItems();
        $modifiers = $order->getModifiers();
        if($order->IsSeparateShippingAddress()) {
            $shipping = $order->ShippingAddress();
        }
        $array = [
             'totalAmount' => [
                'amount' => $order->Total,
                'currency' => $currency->Code,
             ],
             'consumer' => [
                'phoneNumber' => $billing->Phone,
                'givenNames' => $billing->FirstName,
                'surname' => $billing->Surname,
                'email' => $billing->Email,
             ],
            'billing' => [
                'name' => $billing->FirstName.' '.$billing->Surname,
                'line1' => $billing->Address.' '.$billing->Address2,
                'state' => $billing->City,
                'postcode' => $billing->PostalCode,
                'countryCode' => $billing->Country,
                'phoneNumber' => $billing->Phone,
            ],
            'shipping' => [
                'name' => $shipping->FirstName.' '.$shipping->Surname,
                'line1' => $shipping->Address.' '.$shipping->Address2,
                'state' => $shipping->City,
                'postcode' => $shipping->PostalCode,
                'countryCode' => $shipping->Country,
                'phoneNumber' => $shipping->Phone,
            ],
            'merchantReference' => $order->ID,
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
        foreach($items as $item) {
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
        foreach($order->getModifiers() as $modifier) {
            switch($modifier->Type) {
                case 'Discount':
                    $array['discounts'][] = [
                        'displayName' => 'discount',
                        'amount' => $modifier->CalculatedTotal,
                        'currency' => $currency->Code
                    ];
                    break;
                case 'Delivery':
                    $array['shippingAmount'] = [
                        'amount' => $array['shippingAmount']['amount'] + $modifier->CalculatedTotal,
                        'currency' => $currency->Code
                    ];
                    break;
                case 'Tax':
                    $array['taxAmount'] = [
                        'amount' => $array['taxAmount']['amount'] + $modifier->CalculatedTotal,
                        'currency' => $currency->Code
                    ];
                    break;
            }
        }

        return $array;
    }

}
