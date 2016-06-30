<?php
/**
 * クラスオートローダー
 */
class ClassLoader {
	/**
	 * 読み込むディレクトリ一覧
	 */
	private $dirs = array('lib');

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		spl_autoload_register(array($this, 'loader'));
	}

	/**
	 * コールバック
	 * ディレクトリとクラス名を指定して読み込む
	 *
	 * @param string クラス名
	 */
	public function loader($class) {

		foreach ($this->dirs as $dir) {

			$file = './' . $dir . '/' . $class . '.class.php';
			if (is_readable($file)) {
				require $file;
				return true;
			}
		}
	}
}
$classLoader = new ClassLoader();