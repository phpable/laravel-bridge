<?php
namespace Able\LaravelBridge;

use \Illuminate\View\Engines\EngineResolver;
use \Illuminate\View\ViewServiceProvider;
use \Illuminate\View\FileViewFinder;
use \Illuminate\View\Engines\CompilerEngine;

use \Able\LaravelBridge\Sabre\StandardCompiler;

use \Able\Helpers\Arr;

use \Able\IO\Path;
use \Able\IO\Directory;

class BridgeViewServiceProvider extends ViewServiceProvider {

	/**
	 * Register the view finder implementation.
	 * @return void
	 */
	public function registerViewFinder() {
		$this->app->bind('view.finder', function ($app) {
			return new FileViewFinder($app['files'], $app['config']['view.paths'], ['blade.php', 'php', 'css', 'sabre']);
		});
	}

	/**
	 * Extends the view environment.
	 */
	public function registerFactory(){
		parent::registerFactory();
		$this->app['view']->addExtension('sabre', 'sabre-standard');
	}

	/**
	 * Extends the engine resolver instance.
	 */
	public function registerEngineResolver(){
		$this->app->extend('view.engine.resolver', function(EngineResolver $Resolver){
			$this->registerSabreEngine($Resolver);
		});
	}

	/**
	 * Register the Blade engine implementation.
	 * @param  EngineResolver $Resolver
	 */
	public function registerSabreEngine(EngineResolver $Resolver) {
		// The Compiler engine requires an instance of the CompilerInterface, which in
		// this case will be the Sabre compiler, so we'll first create the compiler
		// instance to pass into the engine so it can compile the views properly.

		$this->app->singleton('sabre-standard.compiler', function () {
			return new StandardCompiler($this->app['files'], $this->app['config']['view.compiled'],
				Arr::first($this->app['config']['view.paths']));
		});

		$Resolver->register('sabre-standard', function () {
			return new CompilerEngine($this->app['sabre-standard.compiler']);
		});
	}
}


