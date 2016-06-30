<?php
/**
 * ユーティリティー
 */
class DNNUtil
{
	/**
	 * 平均が$mean、標準偏差が$sigmaの正規分布
	 * ボックス・ミュラー法
	 *
	 * @param float 平均
	 * @param float 標準偏差 
	 */
	public function rnorm($mean, $sigma) {

		// [0, 1]の範囲の疑似乱数を生成
		$x = lcg_value();
		$y = lcg_value();

		// ボックス・ミュラー法を用いて正規乱数を与える
		$ret = $mean + $sigma * sqrt(-2 * log($x)) * cos(2 * M_PI * $y);

		return $ret;
	}

	/**
	 * 配列からランダムに取得
	 *
	 * @param array データセット
	 * @param int サンプリング数
	 * @return array サンプリングしたデータセット
	 */
	public function randomChoice($ary, $count) {

		// 配列のサイズが指定サイズ以下ならそのまま返す
		if (count($ary) <= $count) {
			return $ary;
		}

		// 戻り値用のサンプリングしたデータ
		$newAry = [];
		// 使用済みインデックス
		$used = [];

		while (true) {
			// データセット$aryをランダム選択
			$rKey = mt_rand(0, count($ary) - 1);

			// 既に使われている場合はスキップ
			if (array_key_exists($rKey, $used)) {
				continue;
			}

			$newAry[] = $ary[$rKey];

			// 既定の数になったら　break
			if (count($newAry) == $count) {
				break;
			}

			$used[$rKey] = 1;
		}

		return $newAry;
	}

	/**
	 * 与えられたデータセットから平均と標準偏差を返す
	 *
	 * @param array データセット
	 * @return array 平均、標準偏差
	 */
	public function getMeanAndSD($dataSet) {

		// 合計の計算
		$sum = [];
		// データセット数
		$dataSetCount = count($dataSet);

		for ($i = 0; $i < $dataSetCount; $i++) {
			$items = $dataSet[$i]['data'];
			// 属性情報数
			$itemCount = count($items);

			for ($j = 0; $j < $itemCount; $j++) {

				// 合計の初期化
				if (!isset($sum[$j])){
					$sum[$j] = 0;
				}

				// 各属性を合計していく
				$sum[$j] += $items[$j];
			}
		}

		// 平均
		$means = [];
		$sumCount = count($sum);

		// 各属性の平均値を計算
		for ($i = 0; $i < $sumCount; $i++) {
			$means[$i] = $sum[$i] / $dataSetCount;
		}

		// 差の２乗の合計
		$squaredSum = [];

		for ($i = 0; $i < $dataSetCount; $i++) {

			$items = $dataSet[$i]['data'];
			$itemCount = count($items);

			for ($j = 0; $j < $itemCount; $j++) {

				// 初期化
				if (!isset($squaredSum[$j])) {
					$squaredSum[$j] = 0;
				}

				$squaredSum[$j] += pow(($items[$j] - $means[$j]), 2);
			}
		}

		// 標準偏差
		$sds = [];

		for ($i = 0; $i < $dataSetCount; $i++) {

			$items = $dataSet[$i]['data'];
			$itemCount = count($items);

			for ($j = 0; $j < $itemCount; $j++) {
				$sd = sqrt($squaredSum[$j] / $dataSetCount);
				$sds[$j] = $sd;
			}
		}

		return array('means' => $means, 'sds' => $sds);
	}

	/**
	 * 平均と標準偏差で正規化
	 * 配列を指定された平均と標準偏差で正規化する
	 *
	 * @param array データセット
	 * @param array 平均
	 * @param array 標準偏差
	 * @param array 正規化したデータセット
	 */
	public function normalize($dataAry, $mean, $sd) {

		$newAry = [];
		$dataAryCount = count($dataAry);

		for ($i = 0; $i < $dataAryCount; $i++) {
			$newAry[] = ($dataAry[$i] - $mean[$i]) / $sd[$i];
		}

		return $newAry;
	}
}