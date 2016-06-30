<?php
/**
 * コネクションクラス
 */
class Connection
{
	/**
	 * ユニット（左側）
	 */
	private $leftUnit;

	/**
	 * ユニット（右側）
	 */
	private $rightUnit;

	/**
	 * 重み
	 */
	private $weight;

	/**
	 * 重みの差分（一時保存用）
	 */
	private $weightDiff;

	public function __construct() {
		// // ユニット（左側）
		// $this->leftUnit = new stdClass;

		// // ユニット（右側）
		// $this->rightUnit = new stdClass;

		// 重み
		$this->weight = 1;

		// 重みの差分(一時保存用)
		$this->weightDiff = 0;
	}

	/**
	 * ユニット（左側）の設定
	 */
	public function setLeftUnit($unit) {
		$this->leftUnit = $unit;
	}

	/**
	 * ユニット（右側）の設定
	 */
	public function setRightUnit($unit) {
		$this->rightUnit = $unit;
	}

	/**
	 * ユニット（左側）の取得
	 */
	public function getLeftUnit() {
		return $this->leftUnit;
	}

	/**
	 * ユニット（右側）の取得
	 */
	public function getRightUnit() {
		return $this->rightUnit;
	}

	/**
	 * 重みの設定
	 */
	public function setWeight($weight) {
		$this->weight = $weight;
	}

	/**
	 * 重みの取得
	 */
	public function getWeight() {
		return $this->weight;
	}

	/**
	 * 重みの差分の設定
	 */
	public function setWeightDiff($diff) {
		$this->weightDiff = $diff;
	}

	/**
	 * 重みの差分の取得
	 */
	public function getWeightDiff() {
		return $this->weightDiff;
	}
}