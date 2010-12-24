<?php
/* SVN FILE: $Id$ */
/**
 * インストーラーコントローラー
 *
 * PHP versions 4 and 5
 *
 * BaserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2010, Catchup, Inc.
 *								9-5 nagao 3-chome, fukuoka-shi
 *								fukuoka, Japan 814-0123
 *
 * @copyright		Copyright 2008 - 2010, Catchup, Inc.
 * @link			http://basercms.net BaserCMS Project
 * @package			cake
 * @subpackage		cake.app.controllers
 * @since			Baser v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */
/**
 * インストール条件
 *  @global string PHP_MINIMUM_VERSION
 *  @global integer PHP_MINIMUM_MEMORY_LIMIT in MB
 */
define("PHP_MINIMUM_VERSION","4.3.0");
define("PHP_MINIMUM_MEMORY_LIMIT", 16);
/**
 * インストーラーコントローラー
 */
class InstallationsController extends AppController {
/**
 * クラス名
 *
 * @var		string
 * @access	public
 */
	var $name = 'Installations';
/**
 * コンポーネント
 *
 * @var		array
 * @access	public
 */
	var $components = array('Session');
/**
 * レイアウト
 *
 * @var		string
 * @access	public
 */
	var $layout = "installations";
/**
 * ヘルパー
 *
 * @var		array
 * @access	public
 */
	var $helpers = array('Html', 'Form', 'Javascript', 'Time');
/**
 * モデル
 *
 * @var		array
 * @access	public
 */
	var $uses = null;
/**
 * データベースエラーハンドラ
 *
 * @param int		$errno
 * @param string	$errstr
 * @param string	$errfile
 * @param int		$errline
 * @param string	$errcontext
 * @return void
 * @access public
 */
	function dbErrorHandler( $errno, $errstr, $errfile=null, $errline=null, $errcontext=null ) {

		if ($errno==2) {
			$this->Session->setFlash("データベースへの接続でエラーが発生しました。データベース設定を見直して下さい。<br />".$errstr);
			restore_error_handler();
		}

	}
/**
 * beforeFilter
 *
 * @return void
 * @access public
 */
	function beforeFilter() {

		/* インストール状態判別 */
		if(file_exists(CONFIGS.'database.php')) {
			$db = ConnectionManager::getInstance();
			if($db->config->baser['driver'] != '') {
				$installed = 'complete';
			}else {
				$installed = 'half';
			}
		}else {
			$installed = 'yet';
		}

		switch ($this->action) {
			case 'alert':
				break;
			case 'reset':
				if(Configure::read('debug') != -1) {
					$this->notFound();
				}
				break;
			default:
				if($installed == 'complete') {
					$this->notFound();
				}else {
					if(Configure::read('debug') == 0) {
						$this->redirect(array('action'=>'alert'));
					}
				}
				break;
		}

		if (strpos($this->webroot, 'webroot') === false) {
			$this->webroot = DS;
		}

		$this->theme = null;
		
	}
/**
 * Step 1: ウェルカムページ
 *
 * @return void
 * @access public
 */
	function index() {

		$this->pageTitle = 'BaserCMSのインストール';

		// キャッシュファイルを削除する（デバッグ用）
		if(is_writable(CACHE)) {
			clearAllCache();
		}

	}
/**
 * Step 2: 必須条件チェック
 *
 * @return void
 * @access public
 */
	function step2() {

		if($this->data && $this->data['clicked']=='next'){
			$this->redirect('step3');
		}

		// PHPバージョンチェック
		$phpVersionOk= version_compare ( preg_replace('/[a-z-]/','', phpversion()),PHP_MINIMUM_VERSION,'>=');
		// PHP memory limit チェック
		$phpCurrentMemoryLimit = intval(ini_get('memory_limit'));
		$phpMemoryOk = ((($phpCurrentMemoryLimit >= PHP_MINIMUM_MEMORY_LIMIT) || $phpCurrentMemoryLimit == -1) === TRUE);
		// セーフモード
		$safeModeOff = !ini_get('safe_mode');
		// configs 書き込み権限
		$configDirWritable = is_writable(CONFIGS);
		// core.phpの書き込み権限
		$coreFileWritable = is_writable(CONFIGS.'core.php');
		// DEMO用のページディレクトリの書き込み権限
		$themeDirWritable = is_writable(WWW_ROOT.'themed');
		// 一時フォルダの書き込み権限
		$tmpDirWritable = is_writable(TMP);
		// SQLiteディレクトリ書き込み権限
		$dbDirWritable = is_writable(APP.'db');

		/* ダミーのデータベース設定ファイルを保存 */
		$this->_writeDatabaseConfig();

		/* viewに変数をセット */
		$this->set('phpVersionOk', $phpVersionOk);
		$this->set('phpActualVersion', preg_replace('/[a-z-]/','', phpversion()));
		$this->set('phpMinimumVersion', PHP_MINIMUM_VERSION);
		$this->set('phpMinimumMemoryLimit', PHP_MINIMUM_MEMORY_LIMIT);
		$this->set('phpCurrentMemoryLimit', $phpCurrentMemoryLimit);
		$this->set('phpMemoryOk', $phpMemoryOk);
		$this->set('configDirWritable', $configDirWritable);
		$this->set('coreFileWritable',$coreFileWritable);
		$this->set('safeModeOff', $safeModeOff);
		$this->set('dbDirWritable',$dbDirWritable);
		$this->set('tmpDirWritable',$tmpDirWritable);
		$this->set('themeDirWritable',$themeDirWritable);
		$this->set('blRequirementsMet', ($tmpDirWritable && $configDirWritable && $coreFileWritable && $phpVersionOk && $themeDirWritable));
		$this->pageTitle = 'BaserCMSのインストール [ステップ２]';
		
	}
/**
 * Step 3: データベースの接続設定
 * @return void
 * @access public
 */
	function step3() {	

		if(!$this->data) {
			$this->data = $this->_getDefaultValuesStep3();
		} else {

			$this->_writeDbSettingToSession($this->data['Installation']);

			/* 戻るボタンクリック時 */
			if ($this->data['buttonclicked']=='back') {
				$this->redirect('step2');

			/* 接続テスト */
			} elseif ($this->data['buttonclicked']=='checkdb') {

				$this->set('blDBSettingsOK',$this->_testConnectDb($this->_readDbSettingFromSession()));

			/* 「次のステップへ」クリック時 */
			} elseif ($this->data['buttonclicked']=='createdb') {
				if($this->_constructionDb()) {
					$this->Session->setFlash("データベースの構築に成功しました。");
					$this->redirect('step4');
				}else {
					$db =& ConnectionManager::getDataSource('baser');
					$this->Session->setFlash("データベースの構築中にエラーが発生しました。<br />".$db->error);
				}

			}
			
		}
		
		$this->pageTitle = 'BaserCMSのインストール [ステップ３]';
		$this->set('dbsource', $this->_getDbSource());

	}
/**
 * Step 4: データベース生成／管理者ユーザー作成
 *
 * @return void
 * @access public
 */
	function step4() {

		if(!$this->data) {
			$this->data = $this->_getDefaultValuesStep4();
		} else {
			
			// ユーザー情報をセッションに保存
			$this->Session->write('Installation.admin_email', $this->data['Installation']['admin_email']);
			$this->Session->write('Installation.admin_username', $this->data['Installation']['admin_username']);
			$this->Session->write('Installation.admin_password', $this->data['Installation']['admin_password']);

			if($this->data['clicked'] == 'back') {
				
				$this->_resetDatabase();
				$this->redirect('step3');

			} elseif($this->data['clicked'] == 'finish') {
				
				// DB接続
				$db =& $this->_connectDb($this->_readDbSettingFromSession());

				// サイト基本設定登録
				App::import('Model','SiteConfig');
				$siteConfig['SiteConfig']['email'] = $this->data['Installation']['admin_email'];
				$SiteConfigClass = new SiteConfig();
				$SiteConfigClass->saveKeyValue($siteConfig);

				// 管理ユーザー登録
				$salt = $this->_createKey(40);
				Configure::write('Security.salt',$salt);
				$this->Session->write('Installation.salt',$salt);
				App::import('Model','User');
				$User = new User();
				$user['User']['name'] = $this->data['Installation']['admin_username'];
				$user['User']['real_name_1'] = $this->data['Installation']['admin_username'];
				$user['User']['password'] = Security::hash($this->data['Installation']['admin_password'],null,true);
				$user['User']['user_group_id'] = 1;
				if (!$User->save($user)) {
					if($message) $message .= '<br />';
					$message .= '管理ユーザーを作成できませんでした。<br />'.$db->error;
					$this->Session->setFlash($message);
				} else {
					$this->redirect('step5');
				}
			}
		}

		$this->pageTitle = 'BaserCMSのインストール [ステップ４]';
		
	}
/**
 * Step 5: 設定ファイルの生成
 *
 * データベース設定ファイル[database.php]
 * インストールファイル[install.php]
 * @return void
 * @access public
 */
	function step5() {

		$message = '';

		// インストールファイルを生成する
		if(!$this->_createInstallFile()){
			if($message) $message .= '<br />';
			$message .= '/app/config/install.php インストール設定ファイルの設定ができませんでした。パーミションの確認をして下さい。';
		}

		// tmp フォルダを作成する
		checkTmpFolders();
		
		// ブログの投稿日を更新
		$this->_updateEntryDate();

		// プラグインのステータスを更新
		$this->_updatePluginStatus();

		// ログイン
		$this->_login();

		// テーマを配置する
		$this->_deployTheme();
		$this->_deployTheme('skelton');

		// pagesファイルを生成する
		$this->_createPages();

		// データベース設定を書き込む
		$this->_writeDatabaseConfig($this->_readDbSettingFromSession());

		// デバッグモードを0に変更
		$this->writeDebug(0);

		if($message) {
			$this->setFlash($message);
		}

		$this->pageTitle = 'BaserCMSのインストール完了！';

	}
/**
 * インストールファイルを生成する
 *
 * @return	boolean
 * @access	protected
 */
	function _createInstallFile() {

		$corefilename=CONFIGS.'install.php';
		$installCoreData = array("<?php",	"Configure::write('Security.salt', '".$this->Session->read('Installation.salt')."');",
											"Configure::write('Baser.firstAccess', true);",
											"Configure::write('Cache.disable', false);",
											"Cache::config('default', array('engine' => 'File'));","?>");
		if(file_put_contents($corefilename, implode("\n", $installCoreData))) {
			return chmod($corefilename,0666);
		}else {
			return false;
		}

	}
/**
 * プラグインのステータスを更新する
 *
 * @return	boolean
 * @access	protected
 */
	function _updatePluginStatus() {
		
		$db =& $this->_connectDb($this->_readDbSettingFromSession());
		$version = $this->getBaserVersion();
		App::import('Model', 'Plugin');
		$Plugin = new Plugin();
		$datas = $Plugin->find('all');
		if($datas){
			$result = true;
			foreach($datas as $data) {
				$data['Plugin']['version'] = $version;
				$data['Plugin']['status'] = true;
				if(!$Plugin->save($data)) {
					$result = false;
				}
			}
			return $result;
		} else {
			return false;
		}
		
	}
/**
 * 登録日を更新する
 * 
 * @return	boolean
 * @access	protected
 */
	function _updateEntryDate() {

		$db =& $this->_connectDb($this->_readDbSettingFromSession());
		$db =& $this->_connectDb($this->_readDbSettingFromSession(),'plugin');
		App::import('Model', 'Blog.BlogPost');
		$BlogPost = new BlogPost();
		$blogPosts = $BlogPost->find('all');
		if($blogPosts) {
			$ret = true;
			foreach($blogPosts as $blogPost) {
				$blogPost['BlogPost']['posts_date'] = date('Y-m-d H:i:s');
				if(!$BlogPost->save($blogPost)) {
					$ret = false;
				}
			}
			return $ret;
		} else {
			return false;
		}
		
	}
/**
 * 管理画面にログインする
 *
 * @return	void
 * @access	protected
 */
	function _login() {

		// ログインするとセッションが初期化されてしまうので一旦取得しておく
		$installationSetting = $this->Session->read('Installation');
		Configure::write('Security.salt', $installationSetting['salt']);
		$extra['data']['User']['name'] = $installationSetting['admin_username'];
		$extra['data']['User']['password'] = $installationSetting['admin_password'];
		$this->requestAction('/admin/users/login_exec', $extra);
		$this->Session->write('Installation', $installationSetting);

	}
/**
 * テーマを配置する
 *
 * @param	string	$theme
 * @return	boolean
 * @access	protected
 */
	function _deployTheme($theme = 'demo') {
		
		$targetPath = WWW_ROOT.'themed'.DS.$theme;
        $sourcePath = BASER_CONFIGS.'theme'.DS.$theme;
        $folder = new Folder();
		if($folder->copy(array('to'=>$targetPath,'from'=>$sourcePath,'mode'=>0777,'skip'=>array('_notes')))) {
			if($folder->create($targetPath.DS.'pages',0777)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
		
	}
/**
 * テーマ用のページファイルを生成する
 *
 * @return	boolean
 * @access	protected
 */
	function _createPages() {
		
		App::import('Model','Page');
		$Page = new Page(null, null, 'baser');
		$pages = $Page->findAll();
		if($pages) {
			$ret = true;
			foreach($pages as $page) {
				$Page->data = $page;
				if(!$Page->afterSave()){
					$ret = false;
				}
			}
			return $ret;
		} else {
			return false;
		}

	}
/**
 * データベースに接続する
 *
 * @param	array		$config
 * @return	DboSource	$db
 * @access	public
 */
	function &_connectDb($config, $name='baser') {
		
		if($name == 'plugin') {
			$config['dbPrefix'].=Configure::read('Baser.pluginDbPrefix');
		}
		
		$result =  ConnectionManager::create($name ,array(
				'driver' => $config['dbType'],
				'persistent' => false,
				'host' => $config['dbHost'],
				'port' => $config['dbPort'],
				'login' => $config['dbUsername'],
				'password' => $config['dbPassword'],
				'database' => $this->_getRealDbName($config['dbType'], $config['dbName']),
				'schema' => $config['dbSchema'],
				'prefix' =>  $config['dbPrefix'],
				'encoding' => $config['dbEncoding']));
		
		if($result) {
			return $result;
		} else {
			return ConnectionManager::getDataSource($name);
		}

	}
/**
 * データベースを構築する
 */
	function _constructionDb() {

		if(!$this->_constructionTable(BASER_CONFIGS.'sql')) {
			return false;
		}
		if(!$this->_constructionTable(BASER_PLUGINS.'blog'.DS.'config'.DS.'sql', 'plugin')) {
			return false;
		}
		if(!$this->_constructionTable(BASER_PLUGINS.'feed'.DS.'config'.DS.'sql', 'plugin')) {
			return false;
		}
		if(!$this->_constructionTable(BASER_PLUGINS.'mail'.DS.'config'.DS.'sql', 'plugin')) {
			return false;
		}
		return true;

	}
/**
 * テーブルを構築する
 *
 * @param	string	$configKeyName
 * @param	string	$path
 * @return	boolean
 */
	function _constructionTable($path, $configKeyName = 'baser') {

		$db =& $this->_connectDb($this->_readDbSettingFromSession(), $configKeyName);

		if (!$db->connected && $db->config['driver']!='csv') {
			return false;
		} elseif($db->config['driver'] == 'csv') {
			// CSVの場合はフォルダを作成する
			$folder = new Folder($db->config['database'], true, 0777);
		}

		$folder = new Folder($path);
		$files = $folder->read(true, true, true);
		
		if(isset($files[1])) {

			// DB構築
			foreach($files[1] as $file) {
				if(!preg_match('/\.php$/',$file)) {
					continue;
				}
				if(!$db->createTableBySchema(array('path'=>$file))){
					return false;
				}
			}

			// CSVの場合ロックを解除しないとデータの投入に失敗する
			$db->reconnect();

			// 初期データ投入
			foreach($files[1] as $file) {
				if(!preg_match('/\.csv$/',$file)) {
					continue;
				}
				if(!$db->loadCsv(array('path'=>$file, 'encoding'=>'SJIS'))){
					return false;
				}
			}
		}
		return true;
		
	}
/**
 * ステップ３用のフォーム初期値を取得する
 *
 * @return	array
 * @access	public
 */
	function _getDefaultValuesStep3() {

		if( $this->Session->read('Installation.dbType') ){
			$data = array('Installation'=>$this->_readDbSettingFromSession());
		} else {
			$data['Installation']['dbType'] = 'mysql';
			$data['Installation']['dbHost'] = 'localhost';
			$data['Installation']['dbPort'] = '3306';
			$data['Installation']['dbPrefix'] = 'bc_';
			$data['Installation']['dbName'] = 'baser';
		}
		return $data;

	}
/**
 * ステップ４用のフォーム初期値を取得する
 *
 * @return	array
 * @access	public
 */
	function _getDefaultValuesStep4() {
		
		if ( $this->Session->read('Installation.admin_username') ) {
			$data['Installation']['admin_username'] = $this->Session->read('Installation.admin_username');
		} else {
			$data['Installation']['admin_username'] = 'admin';
		}
		if ( $this->Session->read('Installation.admin_password') ) {
			$data['Installation']['admin_password'] = $this->Session->read('Installation.admin_password');
			$data['Installation']['admin_confirmpassword'] = $data['Installation']['admin_password'];
		} else {
			$data['Installation']['admin_password'] = '';
		}
		if ( $this->Session->read('Installation.admin_email') ) {
			$data['Installation']['admin_email'] = $this->Session->read('Installation.admin_email');
		} else {
			$data['Installation']['admin_email'] = '';
		}
		return $data;

	}
/**
 * DB設定をセッションから取得
 *
 * @return	array
 * @access	protected
 */
	function _readDbSettingFromSession() {

		$data['dbType'] = $this->Session->read('Installation.dbType');
		$data['dbHost'] = $this->Session->read('Installation.dbHost');
		$data['dbPort'] = $this->Session->read('Installation.dbPort');
		$data['dbUsername'] = $this->Session->read('Installation.dbUsername');
		$data['dbPassword'] = $this->Session->read('Installation.dbPassword');
		$data['dbPrefix'] = $this->Session->read('Installation.dbPrefix');
		$data['dbName'] = $this->Session->read('Installation.dbName');
		$data['dbSchema'] = $this->Session->read('Installation.dbSchema');
		$data['dbEncoding'] = $this->Session->read('Installation.dbEncoding');
		return $data;

	}
/**
 * 実際の設定用のDB名を取得する
 *
 * @param	string	$type
 * @param	string	$name
 * @return	string
 * @access	protected
 */
	function _getRealDbName($type, $name) {

		/* dbName */
		if(!empty($type) && !empty($name)) {
			$type = str_replace('_ex','',$type);
			if($type == 'sqlite3') {
				return APP.'db'.DS.'sqlite'.DS.$name.'.db';
			}elseif($type == 'csv') {
				return APP.'db'.DS.'csv'.DS.$name;
			}
		}
		return $name;

	}
/**
 * DB設定をセッションに保存
 *
 * @param	array	$data
 * @return	void
 * @access	public
 */
	function _writeDbSettingToSession($data) {

		/* dbEncoding */
		$data['dbEncoding'] = 'utf8';

		/* dbSchema */
		if($data['dbType'] == 'postgres') {
			$data['dbSchema'] = 'public'; // TODO とりあえずpublic固定
		}else {
			$data['dbSchema'] = '';
		}
		if($data['dbType'] == 'csv') {
			$data['dbEncoding'] = 'sjis';
		}

		$this->Session->write('Installation.dbType', $data['dbType']);
		$this->Session->write('Installation.dbHost', $data['dbHost']);
		$this->Session->write('Installation.dbPort', $data['dbPort']);
		$this->Session->write('Installation.dbUsername', $data['dbUsername']);
		$this->Session->write('Installation.dbPassword', $data['dbPassword']);
		$this->Session->write('Installation.dbPrefix', $data['dbPrefix']);
		$this->Session->write('Installation.dbName', $data['dbName']);
		$this->Session->write('Installation.dbSchema',$data['dbSchema']);
		$this->Session->write('Installation.dbEncoding',$data['dbEncoding']);

	}
/**
 * データベース接続テスト
 *
 * @param	array	$config
 * @return	boolean
 * @access	protected
 */
	function _testConnectDb($config){
		
		set_error_handler(array($this, "dbErrorHandler"));

		/* データベース接続生成 */
		
		$db =& $this->_connectDb($config);
		
		if ($db->connected) {

			/* 一時的にテーブルを作成できるかテスト */
			$randomtablename='deleteme'.rand(100,100000);
			$result = $db->execute("CREATE TABLE $randomtablename (a varchar(10))");

			if (!isset($db->error)) {
				$db->execute("drop TABLE $randomtablename");
				$this->Session->setFlash('データベースへの接続に成功しました。');
				return true;
			} else {
				$this->Session->setFlash("データベースへの接続でエラーが発生しました。<br />".$db->error);
			}

		} else {
			if (!$this->Session->read('Message.flash.message')) {
				if($db->connection){
					$this->Session->setFlash("データベースへの接続でエラーが発生しました。データベース設定を見直して下さい。<br />サーバー上に指定されたデータベースが存在しない可能性が高いです。");
				} else {
					$this->Session->setFlash("データベースへの接続でエラーが発生しました。データベース設定を見直して下さい。");
				}
			}
		}

		return false;
		
	}
/**
 * データベース設定ファイル[database.php]を保存する
 *
 * @param	array	$options
 * @return boolean
 * @access private
 */
	function _writeDatabaseConfig($options = array()) {

		extract($options);
		
		if(!isset($dbType)) {
			$dbType = '';
		}
		if(!isset($dbHost)) {
			$dbHost = 'localhost';
		}
		if(!isset($dbPort)) {
			$dbPort = '';
		}
		if(!isset($dbUsername)) {
			$dbUsername = 'dummy';
		}
		if(!isset($dbPassword)) {
			$dbPassword = 'dummy';
		}
		if(!isset($dbName)) {
			$dbName = 'dummy';
		}
		if(!isset($dbPrefix)) {
			$dbPrefix = '';
		}
		if(!isset($dbSchema)) {
			$dbSchema = '';
		}
		if(!isset($dbEncoding)) {
			$dbEncoding = 'utf8';
		}

		App::import('File');

		$dbfilename=CONFIGS.'database.php';
		$dbfilehandler = & new File($dbfilename);

		if ($dbfilehandler!==false) {

			if ($dbfilehandler->exists()) {
				$dbfilehandler->delete();
			}

			if($dbType == 'mysql' || $dbType == 'sqlite3' || $dbType == 'postgres') {
				$dbType .= '_ex';
			}

			$dbfilehandler->create();
			$dbfilehandler->open('w',true);
			$dbfilehandler->write("<?php\n");
			$dbfilehandler->write("//\n");
			$dbfilehandler->write("// Database Configuration File created by BaserCMS Installation\n");
			$dbfilehandler->write("//\n");
			$dbfilehandler->write("class DATABASE_CONFIG {\n");
			$dbfilehandler->write('var $baser = array('."\n");
			$dbfilehandler->write("\t'driver' => '".$dbType."',\n");
			$dbfilehandler->write("\t'persistent' => false,\n");
			$dbfilehandler->write("\t'host' => '".$dbHost."',\n");
			$dbfilehandler->write("\t'port' => '".$dbPort."',\n");
			$dbfilehandler->write("\t'login' => '".$dbUsername."',\n");
			$dbfilehandler->write("\t'password' => '".$dbPassword."',\n");
			$dbfilehandler->write("\t'database' => '".$this->_getRealDbName($dbType, $dbName)."',\n");
			$dbfilehandler->write("\t'schema' => '".$dbSchema."',\n");
			$dbfilehandler->write("\t'prefix' => '".$dbPrefix."',\n");
			$dbfilehandler->write("\t'encoding' => '".$dbEncoding."'\n");
			$dbfilehandler->write(");\n");
			
			$dbfilehandler->write('var $plugin = array('."\n");
			$dbfilehandler->write("\t'driver' => '".$dbType."',\n");
			$dbfilehandler->write("\t'persistent' => false,\n");
			$dbfilehandler->write("\t'host' => '".$dbHost."',\n");
			$dbfilehandler->write("\t'port' => '".$dbPort."',\n");
			$dbfilehandler->write("\t'login' => '".$dbUsername."',\n");
			$dbfilehandler->write("\t'password' => '".$dbPassword."',\n");
			$dbfilehandler->write("\t'database' => '".$this->_getRealDbName($dbType, $dbName)."',\n");
			$dbfilehandler->write("\t'schema' => '".$dbSchema."',\n");
			$dbfilehandler->write("\t'prefix' => '".$dbPrefix.Configure::read('Baser.pluginDbPrefix')."',\n");
			$dbfilehandler->write("\t'encoding' => '".$dbEncoding."'\n");
			$dbfilehandler->write(");\n");
			$dbfilehandler->write("}\n");
			$dbfilehandler->write("?>\n");

			$dbfilehandler->close();
			return true;

		} else {
			return false;
		}

	}
/**
 * 利用可能なデータソースを取得する
 *
 * @return	array
 * @access	protected
 */
	function _getDbSource() {
		
		/* DBソース取得 */
		$dbsource = array( 'mysql' => 'MySQL', 'postgres' => 'PostgreSQL');
		$folder = new Folder();
		
		/* SQLite利用可否チェック */
		if(class_exists('PDO') && version_compare ( preg_replace('/[a-z-]/','', phpversion()),'5','>=')) {
			
			$pdoDrivers = PDO::getAvailableDrivers();
			if(in_array('sqlite',$pdoDrivers)) {
				$dbFolderPath = APP.'db'.DS.'sqlite';
				if($folder->create($dbFolderPath, 0777) && is_writable($dbFolderPath)){
					$dbsource['sqlite3'] = 'SQLite3';
				}
			}else {
				// TODO SQLite2 実装
				// AlTER TABLE できないので、実装には、テーブル構造の解析が必要になる。
				// 一度一時テーブルを作成し、データを移動させた上で、DROP。
				// 新しい状態のテーブルを作成し、一時テーブルよりデータを移行。
				// その後、一時テーブルを削除する必要がある。
				// 【参考】http://seclan.dll.jp/dtdiary/2007/dt20070228.htm
				// プラグインコンテンツのアカウント変更時、メールフォームのフィールド変更時の処理を実装する必要あり
				//$dbsource['sqlite'] = 'SQLite';
			}
			
		}

		/* CSV利用可否 */
		$dbFolderPath = APP.'db'.DS.'csv';
		if($folder->create($dbFolderPath, 0777) && is_writable($dbFolderPath)){
			$dbsource['csv'] = 'CSV';
		}
		
		return $dbsource;

	}
/**
 * セキュリティ用のキーを生成する
 *
 * @param	int		$length
 * @return	string	キー
 * @access	protected
 */
	function _createKey($length) {

		$keyset = "abcdefghijklmABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$randkey = "";
		for ($i=0; $i<$length; $i++)
			$randkey .= substr($keyset, rand(0,strlen($keyset)-1), 1);
		return $randkey;

	}
/**
 * インストール不能警告メッセージを表示
 * 
 * @return	void
 * @access	public
 */
	function alert() {
		$this->pageTitle = 'BaserCMSのインストールを開始できません';
	}
/**
 * BaserCMSを初期化する
 *
 * debug フラグが -1 の場合のみ実行可能
 * 
 * @return	void
 * @access	public
 */
	function reset() {

		$this->pageTitle = 'BaserCMSの初期化';
		$this->layoutPath = 'admin';
		$this->layout = 'default';
		$this->subDir = 'admin';
		
		if($this->data['Installation']['reset']) {

			if(file_exists(CONFIGS.'database.php')) {
				// データベースのデータを削除
				$this->_resetDatabase();
				unlink(CONFIGS.'database.php');
			}
			if(file_exists(CONFIGS.'install.php')) {
				unlink(CONFIGS.'install.php');
			}

			$messages = array();
			$file = new File(CONFIGS.'core.php');
			$data = $file->read();
			$pattern = '/Configure\:\:write[\s]*\([\s]*\'App\.baseUrl\'[\s]*,[\s]*\'\'[\s]*\);\n/is';
			if(preg_match($pattern, $data)) {
				$data = preg_replace($pattern, "Configure::write('App.baseUrl', env('SCRIPT_NAME'));\n", $data);
				if(!$file->write($data)){
					$messages[] = 'スマートURLの設定を正常に初期化できませんでした。';
				}
				$file->close();
			}
			if(!$this->writeSmartUrl(false)){
				$messages[] = 'スマートURLの設定を正常に初期化できませんでした。';
			}

			$themeFolder = new Folder(WWW_ROOT.'themed');
			$themeFiles = $themeFolder->read(true,true,true);
			foreach($themeFiles[0] as $theme){
				$pagesFolder = new Folder($theme.DS.'pages');
				$pathes = $pagesFolder->read(true,true,true);
				foreach($pathes[0] as $path){
					$folder = new Folder();
					$folder->delete($path);
					$folder = null;
				}
				foreach($pathes[1] as $path){
					if(basename($path) != 'empty') {
						unlink($path);
					}
				}
				$pagesFolder = null;
			}
			$themeFolder = null;

			if($messages) {
				$messages[] = '手動でサーバー上より上記ファイルを削除して初期化を完了させてください。';
			}

			$messages = am(array('BaserCMSを初期化しました。',''),$messages);

			$message = implode('<br />', $messages);

			clearAllCache();
			$this->Session->setFlash($message);
			$complete = true;

		}else {
			$complete = false;
		}

		$this->set('complete', $complete);

	}
/**
 * データベースを初期化する
 * @param array $dbConfig
 */
	function _resetDatabase() {

		/* データベース設定を取得 */
		$dbType = $this->Session->read('Installation.dbType');
		if($dbType) {
			// インストール途中の場合はセッションから取得
			$db = &ConnectionManager::create('test',array(  'driver' => $this->Session->read('Installation.dbType'),
					'persistent' => false,
					'host' => $this->Session->read('Installation.dbHost'),
					'port' => $this->Session->read('Installation.dbPort'),
					'login' => $this->Session->read('Installation.dbUsername'),
					'password' => $this->Session->read('Installation.dbPassword'),
					'database' => $this->Session->read('Installation.dbName'),
					'schema' => $this->Session->read('Installation.dbSchema'),
					'prefix' =>  $this->Session->read('Installation.dbPrefix'),
					'encoding' => 'utf8'));
			$dbConfig = $db->config;
		}elseif(class_exists('DATABASE_CONFIG')) {
			$dbConfig = new DATABASE_CONFIG();
			$dbConfig = $dbConfig->baser;
			if(empty($dbConfig['driver'])) {
				return;
			}
			$db =& ConnectionManager::getDataSource('baser');
		}

		/* 削除実行 */
		// TODO schemaを有効活用すればここはスッキリしそうだが見送り
		$dbType = str_replace('_ex','',$dbConfig['driver']);
		switch ($dbType) {
			case 'mysql':
				$sources = $db->listSources();
				foreach($sources as $source) {
					if(preg_match("/^".$dbConfig['prefix']."([^_].+)$/", $source)) {
						$sql = 'DROP TABLE '.$source;
						$db->execute($sql);
					}
				}
				break;

			case 'postgres':
				$sources = $db->listSources();
				foreach($sources as $source) {
					if(preg_match("/^".$dbConfig['prefix']."([^_].+)$/", $source)) {
						$sql = 'DROP TABLE '.$source;
						$db->execute($sql);
					}
				}
				// シーケンスも削除
				$sql = "SELECT sequence_name FROM INFORMATION_SCHEMA.sequences WHERE sequence_schema = '{$dbConfig['schema']}';";
				$sequences = $db->query($sql);
				$sequences = Set::extract('/0/sequence_name',$sequences);
				foreach($sequences as $sequence) {
					if(preg_match("/^".$dbConfig['prefix']."([^_].+)$/", $sequence)) {
						$sql = 'DROP SEQUENCE '.$sequence;
						$db->execute($sql);
					}
				}
				break;

			case 'sqlite':
			case 'sqlite3':
				@unlink($this->_getRealDbName(str_replace('_ex','',$dbConfig['driver']), $dbConfig['database']));
				break;

			case 'csv':
				$folder = new Folder($this->_getRealDbName(str_replace('_ex','',$dbConfig['driver']), $dbConfig['database']));
				$files = $folder->read(true,true,true);
				foreach($files[1] as $file) {
					if(basename($file) != 'empty') {
						@unlink($file);
					}
				}
				break;

		}

	}

}
?>