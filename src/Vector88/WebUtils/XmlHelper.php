<?php

namespace Vector88\WebUtils;

use Illuminate\Support\Facades\Facade;
 
class XmlHelper extends Facade {
	
    protected static function getFacadeAccessor() {
		return 'XmlHelper';
	}
	
}
