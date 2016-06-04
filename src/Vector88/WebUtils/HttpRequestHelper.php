<?php

namespace Vector88\WebUtils;

use Illuminate\Support\Facades\Facade;
 
class HttpRequestHelper extends Facade {
	
    protected static function getFacadeAccessor() {
		return 'HttpRequestHelper';
	}
	
}
