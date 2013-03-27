<?php

class EMoney extends Extension {

	static function get_default_symbol($currency) {
		$money = new Money();
		return $money->getSymbol($currency);
	}	

	static function get_short_symbol($currency) {
		$symbol = self::get_default_symbol($currency);
		if($symbol) {
			$i = 0;
			while($i < mb_strlen($symbol) && $symbol[$i] === $currency[$i]) {
				$i++;
			}
			return substr($symbol, $i);
		}
	}

	static function get_long_symbol($currency) {
		$symbol = self::get_default_symbol($currency);
		if($symbol && mb_strlen($symbol) < 3) {
			$symbol = substr($currency, 0, 3 - mb_strlen($symbol)) . $symbol;
		}
		return $symbol;
	}

	function NiceDefaultSymbol() {return $this->owner->Nice();}
	function NiceShortSymbol() {return $this->owner->Nice(array('symbol' => self::get_short_symbol($this->owner->currency)));}
	function NiceLongSymbol() {return $this->owner->Nice(array('symbol' => self::get_long_symbol($this->owner->currency)));}
	function NiceDefaultFormat() {$function = EcommerceConfig::get('EMoney', 'default_format'); return $this->owner->$function();}
}