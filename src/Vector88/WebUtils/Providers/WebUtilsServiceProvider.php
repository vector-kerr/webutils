<?php

namespace Vector88\WebUtils\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class WebUtilsServiceProvider extends ServiceProvider
{
    public function boot()
    {
		
    }
	
    public function register()
    {
		
		$this->app->bind( 'XmlHelper', function() {
			return new Vector88\WebUtils\XmlHelper();
		} );
		
		$this->app->bind( 'HttpRequestHelper', function() {
			return new Vector88\WebUtils\HttpRequestHelper();
		} );
		
    }
}
