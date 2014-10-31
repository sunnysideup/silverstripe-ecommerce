<?php

class EcommerceMoney extends Extension {

	public static function get_default_symbol($currency) {
		$money = Money::create();
		return $money->getSymbol($currency);
	}

	public static function get_short_symbol($currency) {
		$symbol = self::get_default_symbol($currency);
		if($symbol) {
			$i = 0;
			while($i < mb_strlen($symbol) && $symbol[$i] === $currency[$i]) {
				$i++;
			}
			return substr($symbol, $i);
		}
	}

	public static function get_long_symbol($currency) {
		$symbol = self::get_default_symbol($currency);
		if($symbol && mb_strlen($symbol) < 3) {
			$symbol = substr($currency, 0, 3 - mb_strlen($symbol)) . $symbol;
		}
		return $symbol;
	}

	function NiceDefaultSymbol($html = true) {
		return self::get_default_symbol($this->owner->currency) == self::get_short_symbol($this->owner->currency) ? $this->NiceShortSymbol($html) : $this->NiceLongSymbol($html);
	}

	function NiceShortSymbol($html = true) {
		$symbol = self::get_short_symbol($this->owner->currency);
		if($html) {
			$symbol = "<span class=\"currencyHolder currencyHolderShort currency{$this->owner->currency}\"><span class=\"currencySymbol\">$symbol</span></span>";
		}
		$amount = $this->owner->getAmount();
		return (is_numeric($amount)) ? $this->owner->currencyLib->toCurrency($amount, array('symbol' => $symbol, 'display' => Zend_Currency::USE_SYMBOL)) : '';
	}

	function NiceLongSymbol($html = true) {
		$symbol = self::get_long_symbol($this->owner->currency);
		$short = self::get_short_symbol($this->owner->currency);
		$pre = substr($symbol, 0, mb_strlen($symbol) - mb_strlen($short));
		if($html) {
			$symbol = "<span class=\"currencyHolder currencyHolderLong currency{$this->owner->currency}\"><span class=\"currencyPreSymbol\">$pre</span><span class=\"currencySymbol\">$short</span></span>";
		}
		else {
			$symbol = $pre.$short;
		}
		$amount = $this->owner->getAmount();
		return (is_numeric($amount)) ? $this->owner->currencyLib->toCurrency($amount, array('symbol' => $symbol, 'display' => Zend_Currency::USE_SYMBOL)) : '';
	}

	function NiceDefaultFormat($html = true) {
		$function = EcommerceConfig::get('EcommerceMoney', 'default_format');
		return $this->owner->$function($html);
	}



}
