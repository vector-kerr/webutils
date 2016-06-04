<?php

namespace Vector88\WebUtils;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class WebUtilsServiceProvider extends ServiceProvider
{
    public function boot()
    {
		
    }
	
    public function register()
    {
		
		App::bind( 'XmlHelper', function() {
			return new \Vector88\WebUtils\XmlHelper();
		} );
		
		App::bind( 'HttpRequestHelper', function() {
			return new \Vector88\WebUtils\HttpRequestHelper();
		} );
		
    }
}
