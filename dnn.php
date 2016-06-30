<?php
// オートローダーのセット
require dirname(__FILE__) . '/core/ClassLoader.php';

/** ユニットタイプ */
const UNIT_TYPE_INPUT	= 0;
const UNIT_TYPE_HIDDEN	= 1;
const UNIT_TYPE_BIAS	= 2;
const UNIT_TYPE_OUTPUT	= 3;

// 引数をチェックする。引数が２以外は終了にする
if ($argc != 2) {
	exit();
}

// irisデータを読み込む
$dataFile = $argv[1];

// 下記のネットワーク構成とします。
// 入力層：４ユニット
// 中間層：４、４ユニット
// 出力層：３ユニット
$numOfUnits = array(
	'numOfUnits' =>  array(4, 4, 4, 3)
);

$dnn = new DNN($numOfUnits);

// 学習係数の設定
$dnn->setLearningCoefficient(0.001);

// fclose($fp);
$data = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// １分割あたりのデータ数を計算
$size = (int)floor(count($data) / 10);

// 全てのデータセット
$allDataSets = [];
$i = 0;

// 精度計算用
$accuracySum = 0;

while (1) {
	// データセット
	$dataSets = [];

	// １分割あたりのデータ数までをdataSetsにセットする
	for ($j = 0; $j < $size; $j++) {

		if ($i > count($data) - 1) {
			break;
		}

		$ary = explode(',', $data[$i]);
		$dataSets[] = $ary;

		$i++;
	}

	// 全データセットに配列として追加する
	$allDataSets[] = $dataSets;

	// 10データセットになったらbreak
	if (count($allDataSets) == 10) {
		break;
	}
}

// 全データセット分ループ
for ($n = 0; $n < count($allDataSets); $n++) {

	// 学習データとテストデータ
	$trainData = [];
	$testData = [];

	//再度全データセット分ループ
	for ($i = 0; $i < count($allDataSets); $i++) {
		// データセットをループ
		for($j = 0; $j < count($allDataSets[$i]); $j++) {

			if ($n === $i) {
				// テストデータを保存（15）
				$testData[] = $allDataSets[$i][$j];
			} else {
				// 学習データを保存（135）
				$trainData[] = $allDataSets[$i][$j];
			}
		}
	}

	// 誤差関数の出力が規定の値未満になるまで最大10000回ループする
	for ($i = 0; $i < 10000; $i++) {

		if ($i % 100 === 0) {
			echo '/_';
		}

		// DNN.phpに渡すデータ
		$inputData = [];

		$trainDataCount = count($trainData);
		for($j = 0; $j < $trainDataCount; $j++) {

			// ラベル部分を除外したもの
			$dataPart = [];

			$trainDataUnitCount = count($trainData[$j]);
			for ($k = 1; $k < $trainDataUnitCount; $k++) {
				$dataPart[] = (int)$trainData[$j][$k];
			}

			// DNN.phpに渡すデータに追加
			$inputData[] = array(
				'expected'	=> (int)$trainData[$j][0],
				'data'		=> $dataPart
			);
		}

		// 学習処理
		$dnn->train($inputData);

		// 1学習後のネットワークに対して入力データによる誤差関数の値を取得
		$result = $dnn->test($inputData);

		// 誤差関数の値が0.01未満であればbreak
		if ($result < 0.01) {
			break;
		}
	}

	// 正解数
	$corrects = 0;

	// テストデータをループしてテスト
	for ($i = 0; $i < count($testData); $i++) {

		// ラベル以外の部分
		$dataPart = [];
		for ($j = 1; $j < count($testData[$i]); $j++) {
			$dataPart[] = (int)$testData[$i][$j];
		}

		// 判定
		$result = $dnn->predict($dataPart);

		// bestには最も値の高かったラベルが入る
		if ($result['best'] === (int)$testData[$i][0]) {
			$corrects++;
		}
	}

	// 1回のループ（学習と判定）における精度の平均値を加算
	$accuracySum += 100 * $corrects / count($testData);
}
$accuracy = $accuracySum / count($allDataSets);
echo 'accuracy: ' . $accuracy . '%';