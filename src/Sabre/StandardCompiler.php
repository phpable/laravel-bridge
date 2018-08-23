<?php
namespace Able\LaravelBridge\Sabre;

use \Illuminate\Filesystem\Filesystem;

use \Illuminate\View\Compilers\CompilerInterface;
use \Illuminate\View\Compilers\Compiler as ACompiler;

use \Able\IO\Path;
use \Able\IO\Writer;
use \Able\IO\Directory;

use \Able\Sabre\Standard\Delegate;

use \Able\Helpers\Arr;

class StandardCompiler extends ACompiler implements CompilerInterface {

	/**
	 * Create a new compiler instance.
	 *
	 * @param  Filesystem $Files
	 * @param  string $cachePath
	 * @param  string $sourcePath
	 * @return void
	 *
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function __construct(Filesystem $Files, string $cachePath, string $sourcePath){
		parent::__construct($Files, $cachePath);
		Delegate::registerSourceDirectory(new Path($sourcePath));
		Delegate::register((new Path(__DIR__))->append('extensions', 'standard.php')->toFile());
 	}

	/**
	 * Get the path to the view minifest.
	 *
	 * @param  string $path
	 * @return string
	 */
	public function getManifestPath(string $path): string {
		return $this->cachePath . '/' . sha1($path) . '.manifest';
	}

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string $path
	 * @return void
	 * @throws \Exception
	 */
	public final function compile($path) {
		(new Path($this->getCompiledPath($path)))->forceFile()
			->purge()->toWriter()->write(Delegate::compile((new Path($path))), Writer::WM_SKIP_EMPTY);

		(new Path($this->getManifestPath($path)))->forceFile()
			->purge()->toWriter()->write(Arr::iterate(Delegate::history()));
	}

	/**
	 * @param string $path
	 * @return bool
	 * @throws \Exception
	 */
	public function isExpired($path): bool {
		$Manifest = (New Path($this->getManifestPath($path)));

		if (!$Manifest->isExists()){
			return true;
		}

		$compiled = $this->getCompiledPath($path);
		foreach ($Manifest->toFile()->toReader()->read() as $filepath){
			if (!is_file($path)){
				return true;
			}

			if ($this->files->lastModified(trim($filepath)) >= $this->files->lastModified(trim($compiled))) {
				return true;
			}
		}

		return false;
	}
}
