<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright		Copyright (c) baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.Controller.Component
 * @since			baserCMS v 4.0.0
 * @license			http://basercms.net/license/index.html
 */

/**
 * baserCMS Contents Component
 *
 * 表示面へのメッセージをコントロールする為のコンポーネント
 *
 * @package Baser.Controller.Component
 */
class BcMessageComponent extends Component {

/**
 * @var FlashComponent FlashComponent
 */
	public $Flash;

/**
 * Initialize
 *
 * @param Controller $controller Controller with components to initialize
 * @return void
 */
	public function initialize(Controller $controller) {
		if(!isset($controller->Flash)) {
			throw new BcException(__d('baser', 'BcMessageComponent を利用するには、コントローラーで FlashComponent の利用設定が必要です。'));
		}
		$this->Flash = $controller->Flash;
	}

/**
 * メッセージをセットする
 *
 * @param string $message メッセージ
 * @param bool $alert 警告かどうか
 * @param bool $saveDblog Dblogに保存するか
 * @param bool $setFlash flash message に保存するか
 */
	public function set($message, $alert = false, $saveDblog = false, $setFlash = true, $class = null) {
		if(!$class) {
			$class = 'notice-message';
			if ($alert) {
				$class = 'alert-message';
			}
		}
		if($setFlash) {
			$this->Flash->set($message, [
				'element' => 'default',
				'params' => ['class' => $class]
			]);
		}
		if ($saveDblog) {
			$AppModel = ClassRegistry::init('AppModel');
			$AppModel->saveDblog($message);
		}
	}

/**
 * 成功メッセージをセットする
 *
 * @param $message
 * @param bool $log DBログに保存するかどうか（初期値 : true）
 */
	public function setSuccess($message, $log = true) {
		$this->set($message, false, $log, true);
	}

/**
 * 失敗メッセージをセットする
 *
 * @param $message
 * @param bool $log DBログに保存するかどうか（初期値 : false）
 */
	public function setError($message, $log = false) {
		$this->set($message, true, $log, true, 'alert-message');
	}

/**
 * 警告メッセージをセットする
 *
 * @param $message
 * @param bool $log DBログに保存するかどうか（初期値 : false）
 */
	public function setWarning($message, $log = false) {
		$this->set($message, true, $log, true, 'warning-message');
	}

/**
 * インフォメーションメッセージをセットする
 *
 * @param $message
 * @param bool $log DBログに保存するかどうか（初期値 : false）
 */
	public function setInfo($message, $log = false) {
		$this->set($message, false, $log, true, 'info-message');
	}

}