<?php
/**
 * ユニットタイプ
 */
class Unit
{

	/**
	 * ユニットタイプ（入力層、中間層、バイアス、出力層）
	 */
	private $unitType;

	/**
	 * ユニット自体に左右の結合オブジェクトへの参照を持たせることでlookupを容易にしています。
	 * 結合オブジェクト（左側）
	 */
	private $leftConnections;

	/**
	 * 結合オブジェクト（右側）
	 */
	private $rightConnections;

	/**
	 * 入力値 全結合層の場合は前の層の全ユニットからの出力の合計とバイアスの合計です。
	 * 今回はバイアスユニットの出力と重みも加算されます。
	 */
	private $inputValue;

	/**
	 * 出力値（バイアスの出力は１固定になります。）
	 */
	private $outputValue;

	/**
	 * デルタ
	 * 誤差逆伝播法で重要な要素になります。ユニットオブジェクトに持たせます。
	 */
	private $delta;

	/**
	 * コンストラクタ
	 *
	 * @param string ユニットタイプ
	 */
	public function __construct($unitType) {

		// ユニットタイプ
		$this->unitType = $unitType;

		// 結合オブジェクト（左側）
		$this->leftConnections = [];

		// 結合オブジェクト（右側）
		$this->rightConnections = [];

		$this->inputValue = 0;

		// 出力値（バイアスの出力は１固定になります。）
		$this->outputValue = 0;
		if ($unitType === UNIT_TYPE_BIAS) {
			$this->outputValue = 1;
		}

		$this->delta = 0;
	}

	/**
	 * ユニットタイプの取得
	 */
	public function getUnitType() {
		return $this->unitType;
	}

	/**
	 * 結合オブジェクト（左側）の配列を設定
	 */
	public function setLeftConnections($connections) {
		// 中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invalid unit type');
		}

		$this->leftConnections = $connections;
	}

	/**
	 * 結合オブジェクト（右側）の配列を設定
	 */
	public function setRightConnections($connections) {
		// 入力層と中間層とバイアスのみ
		if ($this->unitType !== UNIT_TYPE_INPUT &&
			$this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_BIAS) {
			throw new Exception('Invalid unit type');
		}

		$this->rightConnections = $connections;
	}

	/**
	 * 結合オブジェクト（左側）の配列を取得
	 */
	public function getLeftConnections() {
		// 中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invalid unit type');
		}

		return $this->leftConnections;
	}

	/**
	 * 結合オブジェクト（右側）の配列を取得
	 */
	public function getRightConnections() {
		// 入力層と中間層とバイアスのみ
		if ($this->unitType !== UNIT_TYPE_INPUT &&
			$this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_BIAS) {
			throw new Exception('Invalid unit type');
		}

		return $this->rightConnections;
	}

	/**
	 * 入力値を設定
	 */
	public function setInput($value) {

		// 入力層と中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_INPUT &&
			$this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invalid unit type');
		}

		$this->inputValue = $value;
	}

	/**
	 * 入力値を取得
	 */
	public function getInput() {
		// 入力層と中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_INPUT &&
			$this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invalid unit type');
		}

		return $this->inputValue;
	}

	/**
	 * 出力値を設定
	 */
	public function setOutput($value) {
		$this->outputValue = $value;
	}

	/**
	 * 出力値を取得
	 */
	public function getOutput() {
		return $this->outputValue;
	}

	/**
	 * デルタを設定
	 */
	public function setDelta($delta) {

		// 中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invald unit type');
		}

		$this->delta = $delta;
	}

	/**
	 * デルタを取得
	 */
	public function getDelta() {

		// 中間層と出力層のみ
		if ($this->unitType !== UNIT_TYPE_HIDDEN &&
			$this->unitType !== UNIT_TYPE_OUTPUT) {
			throw new Exception('Invalid unit type');
		}

		return $this->delta;
	}
}