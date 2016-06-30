<?php
/**
 * 多層ニューラルネットワーク
 */
class DNN
{
	/** 重み */
	private $DEFAULT_WEIGHT = 0;

	/** ユニット数 */
	private $numOfUnits;

	/** 学習係数 */
	private $learningCoefficient = 0.01;

	/** ミニバッチサイズ */
	private $miniBatchSize = 10;

	/** 接続オブジェクト */
	private $connections;

	/** ユニットオブジェクト */
	private $units;

	/** 入力値の平均 */
	private $inputMeans = [];

	/** 入力値の標準偏差 */
	private $inputSDs = [];

	/**
	 * コンストラクタ
	 *
	 * @param ネットワーク構成等
	 */
	public function __construct($param) {

		// キー名チェック
		if (!array_key_exists('numOfUnits', $param)) {
			trigger_error('numOfUnits must be specified', E_USER_ERROR);
		}

		// ネットワークの層の数が2以下の場合はエラー
		if (count($param['numOfUnits']) <= 2) {
			trigger_error('at least 1 hidden units must be specified', E_USER_ERROR);
		}

		// ユニット数
		$this->numOfUnits = $param['numOfUnits'];

		// 入力値の平均
		if (array_key_exists('means', $param)) {
			$this->inputMeans = $param['means'];
		}

		// 入力値の標準偏差
		if (array_key_exists('sds', $param)) {
			$this->inputSDs = $param['sds'];
		}

		// 各種初期化プロセス
		// すべての層をまとめたもの
		$layersArray = [];

		// 入力層の初期化（バイアスユニットを先頭に持ってきます）
		$inputUnitArray = [];
		$inputUnitArray[] = new Unit(UNIT_TYPE_BIAS);

		for ($i = 0; $i < $this->numOfUnits[0]; $i++) {
			$inputUnitArray[] = new Unit(UNIT_TYPE_INPUT);
		}
		$layersArray[] = $inputUnitArray;

		// 層数
		$numOfUnitsCount = count($this->numOfUnits);

		// 中間層の初期化
		for ($i = 1; $i < $numOfUnitsCount - 1; $i++) {

			$hiddenUnitArray = [];

			// バイアスユニット
			$hiddenUnitArray[] = new Unit(UNIT_TYPE_BIAS);

			// 中間層のユニット
			for ($j = 0; $j < $this->numOfUnits[$i]; $j++) {
				$hiddenUnitArray[] = new Unit(UNIT_TYPE_HIDDEN);
			}
			$layersArray[] = $hiddenUnitArray;
		}

		// 出力層の初期化
		$outputUnitArray = [];
		for ($i = 0; $i < $this->numOfUnits[$numOfUnitsCount - 1]; $i++) {
			$outputUnitArray[] = new Unit(UNIT_TYPE_OUTPUT);
		}
		$layersArray[] = $outputUnitArray;

		// ユニットオブジェクト
		$this->units = $layersArray;

		// Util
		$dnnUtil = new DNNUtil();

		// コネクションの生成
		$allConnectionArray = [];

		// 層の数 - 1 のコネクション「層」が必要
		for ($i = 0; $i < $numOfUnitsCount - 1; $i++) {

			// 現在のコネクション数
			$connectionArray = [];

			// 現在のコネクション層の左側のユニットをループ
			$unitCount = count($this->units[$i]);
			for ($j = 0; $j < $unitCount; $j++) {

				// 左のユニット毎にそのユニットの右に出ているコネクション
				$connArray =  [];

				// 左ユニット
				$leftUnit = $this->units[$i][$j];

				// 右隣のユニットをループ
				$rightUnitCount = count($this->units[$i + 1]);
				for ($k = 0; $k < $rightUnitCount; $k++) {

					$rightUnit = $this->units[$i + 1][$k];

					// 右隣のバイアスユニットは除く
					if ($rightUnit->getUnitType() !== UNIT_TYPE_BIAS) {

						$conn = new Connection();

						// 現在のコネクション層の右側のユニットをセット
						$conn->setRightUnit($rightUnit);

						// 現在のコネクション層の左側のユニットをセット
						$conn->setLeftUnit($leftUnit);

						// 重みの設定
						if ($leftUnit->getUnitType() === UNIT_TYPE_BIAS) {
							// 左のユニットがバイアスユニットの場合
							$conn->setWeight($this->DEFAULT_WEIGHT);
						} else {
							// 左のユニットがバイアスユニットでない場合
							// DEFAULT_WEIGHT(0)に平均0、標準偏差1のガウス分布による乱数の値を足す
							$conn->setWeight($this->DEFAULT_WEIGHT + $dnnUtil->rnorm(0, 1));
						}
						$connArray[] = $conn;

						// 右ユニットの左側コネクションの追加
						$connTmpArray = $rightUnit->getLeftConnections();
						$connTmpArray[] = $conn;
						$rightUnit->setLeftConnections($connTmpArray);
					}
				}

				// 現在のコネクション層に追加
				$connectionArray[] = $connArray;

				// 左ユニットの右側結合にセット
				$leftUnit->setRightConnections($connArray);
			}

			// 全てのコネクションに追加
			$allConnectionArray[] = $connectionArray;
		}

		// 全てのコネクション
		$this->connections = $allConnectionArray;

		// weightsが指定されている場合
		if (array_key_exists('weights', $param)) {

			// 重みのみの配列から重みを対応するconnectionにそれぞれ設定する
			for ($s = 0; $s < count($this->connections); $s++) {
				for ($t = 0; $t < count($this->connections[$s]); $t++) {
					for ($u = 0; $u < count($this->connections[$s][$t]); $u++) {
						$this->connections[$s][$t][$u]->setWeight($param['weights'][$s][$t][$u]);
					}
				}
			}
		}
	}

	/**
	 * 学習係数の設定
	 */
	public function setLearningCoefficient($coefficient) {
		$this->learningCoefficient = $coefficient;
	}

	/**
	 * ミニバッチサイズの設定
	 */
	public function setMiniBatchSize($size) {
		$this->miniBatchSize  = $size;
	}

	/**
	 * モデルの取得
	 * 各層のユニット数と重みを返す
	 * {
	 *   numOfUnits:[入力層のユニット数、中間層１のユニット数、・・・、出力層のユニット数]
	 *   weights:[[重み、・・・]、[・・・]、・・・]
	 *   means:[入力値１の平均、入力値２の平均、・・・]
	 *   sds:[入力値１の標準偏差、入力値２の標準偏差、・・・]
	 * }
	 */
	public function getModel() {
		$weights = [];
		for ($i = 0; $i < count($this->connections); $i++) {
			$weightsSub = [];
			for ($j = 0; $j < count($this->connections[$i]); $j++) {
				$weightsSubSub = [];
				for ($k = 0; $k < count($this->connections[$i][$j]); $k++) {
					$weightsSubSub[] = $this->connections[$i][$j][$k]->getWeight();
				}
				$weightsSub[] = $weightsSubSub;
			}
			$weights[] = $weightsSub;
		}

		return array(
			'numOfUnits'	=> $this->numOfUnits,
			'weights'		=> $weights,
			'means'			=> $this->inputMeans,
			'sds'			=> $this->inputSDs
		);
	}

	/**
	 * 学習
	 * 
	 * 
	 * @param data [{data:[a_1,a_2,...,a_n], expected:c}],[...],...
	 * @return
	 */
	public function train($dataSet) {

		// データセットの平均と標準偏差を取得
		$dnnUtil = new DNNUtil();
		$msd = $dnnUtil->getMeanAndSD($dataSet);

		$this->inputMeans = $msd['means'];
		$this->inputSDs = $msd['sds'];

		// ミニバッチ用データ選択
		$data = $dnnUtil->randomChoice($dataSet, $this->miniBatchSize);
		$dataCount = count($data); // 10

		// {クラス, データ}のペアを繰り返し処理
		for ($n = 0; $n < $dataCount; $n++) {

			// 判定処理を実行して各層の入力、出力を確定させる
			$this->predict($data[$n]['data'], 'train');

			// 誤差逆伝搬（まず重みの差分を計算、あとでまとめて更新する）
			// 出力層から順に。入力層は除く
			$numOfUnitCount = count($this->numOfUnits);
			for ($k = $numOfUnitCount - 1; $k > 0; $k--) {
				// 各ユニットを計算していく
				$currentUnitCount = count($this->units[$k]); // 3, 5, 5
				for ($l = 0; $l < $currentUnitCount; $l++) {

					// ユニット
					$unit = $this->units[$k][$l];

					// デルタ
					$delta = 0;

					// 出力層か中間層のユニットの場合（バイアスは除く）
					if ($unit->getUnitType() === UNIT_TYPE_OUTPUT ||
						$unit->getUnitType() === UNIT_TYPE_HIDDEN) {

						// 出力層のユニットの場合
						if ($unit->getUnitType() === UNIT_TYPE_OUTPUT) {

							// 出力層のデルタは y - d
							$delta = $unit->getOutput();

							// 期待されるクラスの場合は-1する（出力層のデルタは y - d なので）
							if ($data[$n]['expected'] == $l) {
								$delta -= 1;
							}

						// 中間層のユニット（バイアスは除外）
						} else {

							// 入力値
							$inputValue = $unit->getInput();

							// 上位層のユニットを右側結合を通じてループ
							$rightConns = $unit->getRightConnections();

							$rightConnsCount = count($rightConns); // 3, 4
							for ($m = 0; $m < $rightConnsCount; $m++) {

								// デルタ * 重み * 正規化線形関数の１階微分
								$delta += $rightConns[$m]->getRightUnit()->getDelta() * $rightConns[$m]->getWeight() * (($inputValue < 0) ? 0 : 1);
							}
						}

						// デルタをセット
						$unit->setDelta($delta);

						// 重みの差分を前回の逆伝播の結果に追加（左側結合の全て）
						$conns = $unit->getLeftConnections();
						$connCount = count($conns); // 5, 5, 5
						for ($p = 0; $p < $connCount; $p++) {
							$diff = $conns[$p]->getWeightDiff();

							// デルタ * 左側ユニットの出力
							$diff += $delta * $conns[$p]->getLeftUnit()->getOutput();
							$conns[$p]->setWeightDiff($diff);
						}
					}
				}
			}

			// 誤差逆伝播（重みの更新）
			// connectionに重みの差分がセットされているのでそれらを順次適用する
			for ($q = 0; $q < count($this->connections); $q++) {
				for ($r = 0; $r < count($this->connections[$q]); $r++) {
					for ($s = 0; $s < count($this->connections[$q][$r]); $s++) {

						$conn = $this->connections[$q][$r][$s];
						$weight = $conn->getWeight();
						$weight -= $this->learningCoefficient * $conn->getWeightDiff();

						// 新しい重みをセット
						$conn->setWeight($weight);

						// 重みの差分をクリア
						$conn->setWeightDiff(0);
					}
				}
			}
		}
	}

	/**
	 * テスト
	 *
	 * @param data [{data:[a_1, a_2, ..., a_n], expected:c}], [...],...
	 * @return 誤差関数の値
	 */
	public function test($dataSet) {

		// 誤差
		$e = 0;

		// ミニバッチ用データ選択
		$dnnUtil = new DNNUtil();
		$data = $dnnUtil->randomChoice($dataSet, $this->miniBatchSize);

		// {クラス, データ}のペアを繰り返し処理
		for ($n = 0; $n < count($data); $n++) {

			// 判定処理を実行して各層の入力、出力を確定させる
			$this->predict($data[$n]['data'], 'test');

			// 出力層
			$cnt = count($this->numOfUnits) - 1;
			$outputUnits = $this->units[$cnt];

			// 出力層の出力の合計
			$sum = 0;

			// 誤差関数の計算
			for ($i = 0; $i < count($outputUnits); $i++) {

				// クラスが一致した場合のみ出力の対数値を加算（クラスが一致しない場合は0をかけるので）
				if ($data[$n]['expected'] == $i) {
					$sum += log($outputUnits[$i]->getOutput());
				}
			}

			// 符号を反転して加算
			$e += -1 * $sum;
		}

		// eの平均値を返す
		$avg_e = $e / count($data);

		return $avg_e;
	}

	public function predict($dataSet, $debug = null) {

		// ソフトマックス関数の計算用
		$denom = 0;
		$denomArray = [];

		// データセットを正規化する
		$dnnUtil = new DNNUtil();
		$data = $dnnUtil->normalize($dataSet, $this->inputMeans, $this->inputSDs);

		// バイアスを除く入力層のユニット全部
		// 現在の層のユニット全部
		$g = 0;

		for ($i = 0; $i < count($this->units[0]); $i++) {
			// 入力層がバイアスユニットではない場合
			if ($this->units[0][$i]->getUnitType() !== UNIT_TYPE_BIAS) {

				// 入力層は入力値 = 出力値
				$this->units[0][$i]->setInput($data[$g]);
				$this->units[0][$i]->setOutput($data[$g]);

				$g++;
			}
		}

		// 入力層以降から繰り返し
		$unitCount = count($this->units); // 4
		for ($i = 1; $i < $unitCount; $i++) {

			// 現在の層のユニット全部
			$currentUnitCount = count($this->units[$i]); // 5, 5, 3
			for ($j = 0; $j < $currentUnitCount; $j++) {

				$unit = $this->units[$i][$j];

				// 中間層と出力層の場合（バイアスの出力は常に１なので処理しない）
				if ($unit->getUnitType() === UNIT_TYPE_HIDDEN ||
					$unit->getUnitType() === UNIT_TYPE_OUTPUT) {

					// 左側ユニットの出力 * 重みの合計
					$sum = 0;

					// 左結合
					$connArray = $unit->getLeftConnections();
					$connArrayCount = count($connArray); // 5

					// p - 1層の総入力を計算
					for ($k = 0; $k < $connArrayCount; $k++) {
						$conn = $connArray[$k];
						// 左側ユニットの出力 * 左側結合の重みを加算
						$sum += $conn->getLeftUnit()->getOutput() * $conn->getWeight();
					}

					// 現在の層のユニットにセットする
					$unit->setInput($sum);

					if ($unit->getUnitType() === UNIT_TYPE_OUTPUT) {
						// 出力層の場合はソフトマックス関数計算用変数をセット

						$ex = exp($unit->getInput());

						$denom += $ex;
						$denomArray[] = $ex;

					} else {
						// 中間層の場合は正規化線形関数を通す
						if ($unit->getInput() < 0) {
							// ユニットの入力値がマイナスの場合は出力を0に
							$unit->setOutput(0);
						} else {
							// ユニットの入力値が0以上の場合はそのまま出力
							$unit->setOutput($unit->getInput());
						}
					}
				}
			}
		}

		// ソフトマックス関数計算用変数からOUTPUTユニットの最終結果を計算
		$result = [];
		$outputUnits = $this->units[count($this->numOfUnits) - 1];

		// 出力層のユニット数
		$denomArrayCount = count($denomArray); // 3
		for ($p = 0; $p < $denomArrayCount; $p++) {
			$res = $denomArray[$p] / $denom;
			// echo $denomArray[$p] . ' ' . $denom . ' ' . $res . "\n\n";
			$result[] = $res;

			// outputに出力値をセットする。学習時の誤差関数に出力が必要になる
			$outputUnits[$p]->setOutput($res);
		}

		// 最も確率の高い結果を調べる
		$best = -1;
		$idx = -1;

		$resultCount = count($result);
		for ($i = 0; $i < $resultCount; $i++) {
			if ($best < $result[$i]) {
				$best = $result[$i];
				$idx = $i;
			}
		}

		return array('best' => $idx, 'result' => $result);
	}
}