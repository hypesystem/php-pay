<?php

class PayPalAdapter extends PaymentAdapter {
    public function __construct($returnUrl, $cancelUrl, $options = array(), $identifyingArgumentName = "id", $useSandbox = false) {
        $this->requireUrl($returnUrl);
        $this->requireUrl($cancelUrl);
        $this->requireOptions($options, array("USER", "PWD", "SIGNATURE"));
        
        //Handle sandbox/not sandbox
        $this->apiUrl = "https://api-3t.paypal.com/nvp";
        $this->expressCheckoutUrl = "https://www.paypal.com/cgi-bin/webscr";
        if($useSandbox) {
            $this->apiUrl = "https://api-3t.sandbox.paypal.com/nvp";
            $this->expressCheckoutUrl = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        }
        $this->sandbox = $useSandbox;
        
        $this->identifyingArgumentName = $identifyingArgumentName;
        
        $this->returnUrl = $returnUrl;
        $this->cancelUrl = $cancelUrl;

        //Get options/reasonable defaults
        $this->options = array(
            "VERSION" => 109.0,
            "SOLUTIONTYPE" => "Sole",
            "LANDINGPAGE" => "Billing",
            "PAYMENTREQUEST_0_PAYMENTACTION" => "Sale"
        );
        foreach($options as $key => $value) {
            $key = strtoupper($key);
    
            if($key == "VERSION" && $value != $this->options["VERSION"]) {
                trigger_error("The PayPalAdapter is configured with a custom version '$value', but the library is only guaranteed to work with '{$this->options["VERSION"]}'.", E_USER_WARNING);
            }
            if($key == "RETURNURL" || $key == "CANCELURL") {
                throw new InvalidArgumentException("Cannot set $key in PayPalAdapter. It must be set as an argument in the constructor.");
            }

            $this->options[$key] = $value;
        }
    }
    
    public function preparePayment($identifyingValue, Order $order) {
        $options = $this->getOptionsWithLineItems($order);
        
        $options["NOSHIPPING"] = 1;
        if($order->hasShipping()) {
            $options["NOSHIPPING"] = 0;
            $options["PAYMENTREQUEST_0_SHIPPINGAMT"] = urlencode(number_format($order->getShippingPrice(), 2));
        }
        
        $options["RETURNURL"] = $this->composeReturnUrl($identifyingValue);
        $options["CANCELURL"] = $this->composeCancelUrl($identifyingValue);
        $options["METHOD"] = "SetExpressCheckout";
    }
    
    private function getOptionsWithLineItems(Order $order) {
        $options = $this->options;
        $total = 0;
        $lineItemCount = $order->getLineCount();
        for($i = 0; $i < $lineItemCount; $i++) {
            $item = $order->getLine($i);
            
        }
    }
    
    private function composeReturnUrl($id) {
        return $this->composeUrl($this->returnUrl, array(
            $this->identifyingArgumentName => $id
        ));
    }
    
    private function composeUrl($url, $queries) {
        //Do something clever...
        return $url.$this->buildQueryString($queries);
    }
    
    private function buildQueryString($queryMap) {
        $queryStrings = array();
        foreach($queryMap as $key => $value) {
            $queryStrings[] = $key."=".$value;
        }
        return "?".implode("&",$queryStrings);
    }
    
    private function composeCancelUrl($id) {
        return $this->composeUrl($this->cancelUrl, array(
            $this->identifyingArgumentName => $id
        ));
    }
}

?>
