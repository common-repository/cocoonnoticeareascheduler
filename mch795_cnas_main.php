<?php


class Mch795_Cocoon_Notice_Area_Scheduler {


	const STATIC_FILE_VER       = '1';
	const APP_PREFIX            = 'mch795_cnas';
	const APP_TITLE             = 'CocoonNoticeAreaScheduler';
	const APP_WP_NONCE_KEY      = 'mch795_cnas_my_wpnonce';
	const APP_LANG_DMN          = 'mch795-cocoon-notice-area-scheduler';
	const APP_POST_TYPE         = 'mch795_cnaspt';
	// 保存期間
	const EXPIRED_TIME          = 24 * 60 * 60;
	const FORM_KEY_FROM_PAGE    = 'from_page';


	public $admin_style_css_url = '';
	public $admin_script_url    = '';


	// <editor-fold desc="util">

	/**
	 * 表示用テキスト
	 * @param string $text
	 * @return string
	 */
	public function _t($text){
		return __($text);
//		return __($text, self::APP_LANG_DMN);
	}

	/**
	 * add prefix text
	 * @param string $text
	 * @return string
	 */
	public function __ap($text){
		return $this->add_prefix($text);
	}
	/**
	 * add prefix text
	 * @param $text
	 * @return string
	 */
	private function add_prefix($text) {
		return self::APP_PREFIX . '_' . $text;
	}

	public static function get_object() {
		static $instance = null;
		if ( NULL === $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	// </editor-fold>


	public static function myplugin_load_textdomain() {
		load_plugin_textdomain( self::APP_LANG_DMN );
	}

	public function __construct(){

		$isCocoon = $this->isCocoonTheme();

		if ( is_admin() ) {
			$this->admin_style_css_url = plugins_url( 'css/admin_style.css', __FILE__ ) . '?v=' . self::STATIC_FILE_VER;
			$this->admin_script_url    = plugins_url( 'js/admin.js'        , __FILE__ ). '?v=' . self::STATIC_FILE_VER;

			add_action('admin_init', array($this, 'admin_init'));
			add_action('admin_enqueue_scripts', array($this, 'admin_load_styles'));
			add_action('admin_enqueue_scripts', array($this, 'add_admin_script'));


			add_action( 'admin_notices', [$this, 'displaySettingCheckResult']);

			// <editor-fold desc="style管理画面 新規追加・編集">
			add_action( 'save_post_'. self::APP_POST_TYPE, array( $this, 'save_post_data') );
			add_filter( 'views_edit-'.self::APP_POST_TYPE, [$this, 'manager_list_custom_filter']);
			add_filter( 'manage_' . self::APP_POST_TYPE . '_posts_columns', [ $this, 'manager_list_columns']);
			add_action( 'manage_' . self::APP_POST_TYPE . '_posts_custom_column', array( $this, 'manager_list_column_value'), 10, 2 );


			// 一覧のカラム拡張
			add_filter('manage_edit-' . self::APP_POST_TYPE . '_sortable_columns', [$this, 'manager_list_manage_sortable']);
			// 並び替え処理
			add_filter('request', [$this, 'manager_list_order_setting']);
			// </editor-fold>

			add_filter('post_row_actions', [$this, 'post_make_cnas_link_row'] ,10,2);
			add_filter('page_row_actions', [$this, 'post_make_cnas_link_row'],10,2);

			// links on the plugin page
			add_filter('plugin_row_meta', [$this, 'registerPluginLinks'], 10, 2);


			add_action( 'init', array( $this, 'create_link_post_type' ) );
			add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
			add_action( 'admin_menu', [ $this, 'add_meta_boxes' ] );

		} else {
			if ($isCocoon) {
				$this->readEntryInit();
			}
		}
	}

	
	
	
	// <editor-fold desc="公開用">


	private function readEntryInit()
	{
		static $postMetaDataMap = null;
		if ($postMetaDataMap !== null) {
			return $postMetaDataMap;
		}
		$postMetaDataMap = $this->getCurrentPublicPostData();

		require_once __DIR__ . '/lib/ccnNoticeAreaLib.php';
		return $postMetaDataMap;
	}


	/**
	 * @return array
	 */
	private function getNoticeAreaItem(){
		static $noticeAreaItem = null;
		if ($noticeAreaItem !== null) {
			return $noticeAreaItem;
		}


		if (!$this->isCocoonNoticeAreaVisible()) {
			return [];
		}

		$postMetaDataMap = $this->readEntryInit();
		$noticeAreaItem  = $this->choiceUseNoticeMsgItem($postMetaDataMap);
		return $noticeAreaItem;
	}


	public function getNoticeMessage(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['post_content'])) {
			return $noticeAreaItem['post_content'];
		}
		return '';
	}

	public function getNoticeUrl(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['url'])) {
			return $noticeAreaItem['url'];
		}
		return '';
	}


	public function getNoticeBackColor(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['bgColor']) && $noticeAreaItem['bgColor']) {
			return $noticeAreaItem['bgColor'];
		}
		return '';
	}


	public function getNoticeTextColor(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['txtColor']) && $noticeAreaItem['txtColor']) {
			return $noticeAreaItem['txtColor'];
		}
		return '';
	}


	public function getNoticeLinkTargetBlank(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['targetBlank'])) {
			return $noticeAreaItem['targetBlank'];
		}
		return false;
	}


	public function getNoticeTypeStr(){
		$noticeAreaItem = $this->getNoticeAreaItem();
		if (isset($noticeAreaItem['noticeType']) && $noticeAreaItem['noticeType']) {
			return $noticeAreaItem['noticeType'];
		}

		if (defined('OP_NOTICE_TYPE')) {
			return get_theme_option(OP_NOTICE_TYPE);
		} else {
			return '';
		}
	}







	private function choiceUseNoticeMsgItem($messageList){

//		$currentDate = date('YmdHi');
		$datetime = current_datetime();
		$currentDate = $datetime->format( 'YmdHi' ) ;
		$useMsgItem = [];
		if (isset($messageList[self::NOTICE_TARGET_TYPE_DEFAULT]) ) {
			$defaults = [];
			foreach ($messageList[self::NOTICE_TARGET_TYPE_DEFAULT] as $msgItem) {
				if (!$this->checkMsgReleaseDate($currentDate, $msgItem)) {
					continue;
				}
				$defaults[] = $msgItem;
			}

			if ($defaults) {
				$shuffles = $defaults;
				shuffle($shuffles);
				$useMsgItem = reset($shuffles );
			}
		}


		foreach ($messageList as $type => $msgItems) {
			if (!$msgItems || $type === self::NOTICE_TARGET_TYPE_DEFAULT) {
				continue;
			}
			shuffle($msgItems);
			foreach ($msgItems as $msgItem) {
				if (!$msgItem[self::ITEM_NAME_IDS] || !$msgItem['post_content']) {
					continue;
				}

				if (!$this->checkMsgReleaseDate($currentDate, $msgItem)) {
					continue;
				}

				if ($type === self::NOTICE_TARGET_TYPE_PAGE) {
					if (is_page( $msgItem[self::ITEM_NAME_IDS] )) {
						$useMsgItem = $msgItem;
						break 2;
					}
				}
				else if ($type === self::NOTICE_TARGET_TYPE_SINGLE) {
					// 通常投稿ページ
					if (is_single( $msgItem[self::ITEM_NAME_IDS] )) {
						$useMsgItem = $msgItem;
						break 2;
					}
				}
				else if ($type === self::NOTICE_TARGET_TYPE_CATEGORY) {
					if (/*is_single() &&*/ in_category( $msgItem[self::ITEM_NAME_IDS] )) {
						$useMsgItem = $msgItem;
						break 2;
					}
				}
			}
		}

		return $useMsgItem;
	}


	private function checkMsgReleaseDate($currentDate, $msgItem){
		// <editor-fold desc="期間チェック">
		if (isset($msgItem['startDate']) && $msgItem['startDate']) {
			if ($currentDate < $msgItem['startDate']) {
				return false;
			}
		}
		if (isset($msgItem['endDate']) && $msgItem['endDate']) {
			if ($currentDate > $msgItem['endDate']) {
				return false;
			}
		}
		// </editor-fold>

		return true;
	}





	/**
	 * 公開中のデータを取得
	 * @return array
	 */
	private function getCurrentPublicPostData(){

		$postMetaDataMap = get_transient($this->getPublishDataKey());
		if ($postMetaDataMap !== false) {
			return $postMetaDataMap;
		}

		$args = [
			'post_type'   => self::APP_POST_TYPE,
			'post_status' => [
				'publish',
			],
		];

		global $wp_query;
		$the_query = new WP_Query( $args );
		$wp_query = $the_query;

		$publishPostDataMap = [];
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();

				$post_id = get_the_ID();
				$postItem = [];
				$postItem['post_id'] = $post_id;
				$postItem['title'] = get_the_title();
				$postItem['content'] = get_the_content();

				$publishPostDataMap[$post_id] = $postItem;
			}
			/* Restore original Post Data */
			wp_reset_postdata();
		}

		$postMetaDataMap = [];
		if($publishPostDataMap){
			$currentDate = strtotime('now');
			foreach ($publishPostDataMap as $postItem){
				$post_id = $postItem['post_id'];

				$endAt = $this->getCustomPostMetaData($post_id, self::ITEM_NAME_END);
				if (!$endAt) {
					continue;
				}
				$endTime = strtotime($endAt);
				if ($endTime < $currentDate) {
					continue;
				}

				$metaTmp = [];
				foreach ($this->getNoticeAreaInfo() as $index => $postMetaData) {
					$postMetaKey = $postMetaData['name'];
					if ($postMetaKey == self::ITEM_NAME_END) {
						continue;
					}

					$data = $this->getCustomPostMetaData($post_id, $postMetaKey);
					$metaTmp[$postMetaKey] = $data;
				}

				$metaTmp[self::ITEM_NAME_END] = $endAt;
				$metaTmp['startDate']    = date('YmdHi', strtotime($metaTmp[self::ITEM_NAME_START]));
				$metaTmp['endDate']      = date('YmdHi', $endTime);
				$metaTmp['post_id']      = $post_id;
				$metaTmp['post_content'] = $postItem['content'];

				$metaTmp[self::ITEM_NAME_IDS]  = $this->convertIntArr($metaTmp[self::ITEM_NAME_IDS]);

				$targetType = $metaTmp[self::ITEM_NAME_TARGET_TYPE];
				if (!isset($postMetaDataMap[$targetType])) {
					$postMetaDataMap[$targetType] = [];
				}
				$postMetaDataMap[$targetType][$post_id] = $metaTmp;
			}
		}

		set_transient($this->getPublishDataKey(), $postMetaDataMap, self::EXPIRED_TIME);

		return $postMetaDataMap;
	}

	private function getPublishDataKey(){
		return $this->__ap('publish_data');
	}

	private function deleteCacheData(){
		delete_transient($this->getPublishDataKey());
	}

	// </editor-fold>


	private function convertIntArr($csvStr){
		return array_map('intval', explode(',', $csvStr ) );
	}


	public function isCocoonNoticeAreaVisible(){
		if ( !$this->isCocoonTheme() ){
			return false;
		}

		if (!function_exists( 'is_notice_area_visible' )) {
			// 廃止された場合無効にする
			return false;
		}

		if( is_notice_area_visible() ) {
			return true;
		}

		return false;
	}

	private function isCocoonTheme(){
		$currentTemplate = get_template();
		if ($currentTemplate == 'cocoon-master') {
			return true;
		}
		return false;
	}



	// <editor-fold desc="メニュー">



	function post_make_cnas_link_row($actions, $post) {
		if ($this->post_is_post_type_enabled($post->post_type)) {
			$title = _draft_or_post_title( $post );
			$actions['clone'] = '<a href="'. $this->generateClonePostLink( (int)$post->ID , 'display', false).'">'
				. $this->_t('複製') . '</a>';
		}
		return $actions;
	}

	/**
	 * Test if post type is enable to be copied
	 */
	private function post_is_post_type_enabled($post_type){
		if ($post_type !== self::APP_POST_TYPE) {
			return false;
		}

		return true;
	}

	private function generateClonePostLink($id = 0, $context = 'display', $draft = true ) {
		if ( !$post = get_post( $id ) ){
			return '';
		}

		if(!$this->post_is_post_type_enabled($post->post_type)){
			return '';
		}

		if ( 'display' == $context ){
			$action = '?post_type=' . self::APP_POST_TYPE . '&amp;copy_id='.(int)$post->ID;
		} else {
			$action = '?post_type=' . self::APP_POST_TYPE . '&copy_id='.(int)$post->ID;
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( !$post_type_object ){
			return '';
		}

		return wp_nonce_url(  admin_url( 'post-new.php'. $action ) , $this->__ap('post_clone_link_') . (int)$post->ID);
	}


	public function add_menu_page() {
//		add_submenu_page( 'options-general.php',
//			$this->_t('Cocoon通知エリア スケジューラー'),
//			$this->_t('Cocoon通知エリア スケジューラー'),
//			'manage_options',
//			MCH_MCSCM_PLUGIN_DIR . 'mch-cocoon-notice-area-scheduler.php',
//			array($this, 'general_option_page')
//		);
	}


	/**
	 */
	function create_link_post_type() {

		register_post_type(
			self::APP_POST_TYPE,
			array(
				'label'					=> $this->_t('通知エリア管理'),
				'public'				=> false,
				'publicly_queryable'	=> false,
				'has_archive'			=> false,
				'show_ui'				=> true,
				'exclude_from_search'	=> true,
				'menu_position'			=> 22,
				'supports'				=> [ 'title' ],
				'menu_icon'				=> 'dashicons-calendar',
				'rewrite' => array('slug' => self::APP_PREFIX . '_'),

			)
		);

	}


	// </editor-fold>


	// <editor-fold desc="編集画面">

	const FORM_TYPE_TEXT         = 'text';
	const FORM_TYPE_CHECKBOX     = 'checkbox';
	const FORM_TYPE_COLOR_SELECT = 'colorselect';
	const FORM_TYPE_DATETIME     = 'datetime';
	const FORM_TYPE_LIST         = 'list';

	const NOTICE_TARGET_TYPE_DEFAULT   = 0;
	const NOTICE_TARGET_TYPE_PAGE      = 1;
	const NOTICE_TARGET_TYPE_SINGLE    = 2;
	const NOTICE_TARGET_TYPE_CATEGORY  = 3;


	const NOTICE_TARGET_TYPE_LIST = [
		self::NOTICE_TARGET_TYPE_DEFAULT    => 'デフォルト',
		self::NOTICE_TARGET_TYPE_PAGE       => '固定ページ',
		self::NOTICE_TARGET_TYPE_SINGLE     => '投稿ページ',
		self::NOTICE_TARGET_TYPE_CATEGORY   => 'カテゴリー指定',
	];


	public function the_checkbox_checked($val1, $val2 = 1){
		if ( $val1 == $val2 ) {
			echo ' checked="checked"';
		}
	}

	public function the_select_selected($val1, $val2 = 1){
		if ( $val1 == $val2 ) {
			echo ' selected="selected"';
		}
	}

	public function generate_tips_tag($caption){
		$tag = '<div><p class="tips"><span class="fa fa-info-circle" aria-hidden="true"></span> '. $this->_t( $caption ).'</p></div>';
		echo $tag;
	}


	function generate_color_picker_tag($name){
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		$data = <<<TEXT
(function( $ ) {
	var options = {
		defaultColor: false,
		change: function(event, ui){},
		clear: function() {},
		hide: true,
		palettes: true
	};
	$("input:text[name='$name']").wpColorPicker(options);
})( jQuery );
TEXT;
		wp_add_inline_script( 'wp-color-picker', $data, 'after' ) ;
	}

	const ITEM_NAME_TARGET_TYPE = 'targetType';
	const ITEM_NAME_START       = 'startAt';
	const ITEM_NAME_END         = 'endAt';
	const ITEM_NAME_IDS         = 'ids';
	const ITEM_NAME_TXT_COLOR   = 'txtColor';
	const ITEM_NAME_BG_COLOR    = 'bgColor';

	public function getNoticeAreaInfo(){
		return [
			[
				'name' => 'url',
				'title' => '通知URL',
				'formType' => self::FORM_TYPE_TEXT,
				'afterTipsLabel' => '通知エリアにリンクを設定する場合はURLを入力してください。',
				'dataType' => 'URL',
			],
			[
				'name' => self::ITEM_NAME_TARGET_TYPE,
				'title' => '指定方法',
				'formType' => self::FORM_TYPE_LIST,
				'list' => self::NOTICE_TARGET_TYPE_LIST,

			],
			[
				'name' => self::ITEM_NAME_IDS,
				'title' => 'ID',
				'formType' => self::FORM_TYPE_TEXT,
				'afterTipsLabel' => '記事IDやカテゴリーIDを半角カンマ区切りで入力してください。デフォルトは不要です。',
			],
			[
				'name' => 'noticeType',
				'title' => '通知タイプ',
				'formType' => self::FORM_TYPE_TEXT,
				'afterTipsLabel' => '通知の種類を入力してください。 notice(緑色)、warning(黄色)、danger(赤色)。未入力の場合はCocoonの設定値になります。',
			],

			[
				'name' => self::ITEM_NAME_BG_COLOR,
				'title' => '背景色',
				'formType' => self::FORM_TYPE_COLOR_SELECT,
			],
			[
				'name' => self::ITEM_NAME_TXT_COLOR,
				'title' => '文字色',
				'formType' => self::FORM_TYPE_COLOR_SELECT,
			],
			[
				'name' => 'targetBlank',
				'title' => '新しいタブで開くか',
				'formType' => self::FORM_TYPE_CHECKBOX,
				'afterTipsLabel' => '通知エリアのリンクをtarget="_blank"で開くかどうか。',
				'afterLabel' => '通知リンクを新しいタブで開く',

			],
			[
				'name' => self::ITEM_NAME_START,
				'title' => '開始日時',
				'formType' => self::FORM_TYPE_DATETIME,
				'afterLabel' => ' から',

			],
			[
				'name' => self::ITEM_NAME_END,
				'title' => '終了日時',
				'formType' => self::FORM_TYPE_DATETIME,
				'afterLabel' => ' まで',

			],


		];
	}

	/**
	 * 管理画面の初期設定
	 */
	public function admin_init() {
		wp_register_script(
			$this->__ap( 'admin_script' ),
			$this->admin_script_url,
			array( 'jquery' ),
			null
		);
	}



	public function admin_load_styles() {
		wp_register_style( $this->add_prefix( 'adminStylesheet' ), $this->admin_style_css_url, [], null );
		wp_enqueue_style( $this->add_prefix( 'adminStylesheet' ) );
	}

	public function add_admin_script()
	{

	}

	/**
	 * 投稿・編集画面入力欄生成
	 */
	function add_meta_boxes() {
		add_meta_box(
			$this->__ap('input_form_main'),
			$this->_t('通知エリア設定を入力'),
			[ $this, 'input_form_main_contents']
			,self::APP_POST_TYPE
			, 'normal'
		);
	}


	/**
	 * メイン入力欄
	 */
	function input_form_main_contents()
	{
		/** @var WP_Post $post */
		global $post;
		global $post_ID;

		if (isset($_GET['copy_id']) && $_GET['copy_id']) {
			$post_id   = (int)$_GET['copy_id'];
			$copy_post = null;
			if ($post_id) {
				/** @var WP_Post $copy_post */
				$copy_post = get_post( $post_id );
			}

			if ($copy_post) {
				$post->post_content = $copy_post->post_content;
			}
		}

		include_once 'ui/input-form-main.php';
	}


	const DB_META_SAVE_KEY = 'item';

	/**
	 * postmetaのデータを取得する
	 * @param $post_id
	 * @param $key
	 * @param bool $isAddAppPrefix
	 * @return mixed
	 */
	public function getCustomPostMetaData($post_id, $key, $isAddAppPrefix=true){
		if($isAddAppPrefix){
			$key  = $this->__ap($key);
		}
		$data = get_post_meta( $post_id, $key , true);
		return $data;
	}


	/**
	 * @param int $post_id
	 */
	function save_post_data($post_id ) {
		$this->save_post_meta_data($post_id);
	}

	/**
	 * @param int $post_id
	 */
	function save_post_meta_data($post_id) {
		//更新時にはキャッシュ削除
		delete_transient( $this->__ap( self::DB_META_SAVE_KEY . '_' . $post_id ) );

		$fromKey = $this->__ap( self::FORM_KEY_FROM_PAGE);
		//メインのページだけ更新させる
		if ( !empty($_POST) && isset($_POST[$fromKey]) && $_POST[$fromKey] === 'main' ) {
			$new_datas = [];
			foreach ($this->getNoticeAreaInfo() as $index => $postMetaData) {
				$postMetaKey = $this->__ap($postMetaData['name']);
				if ($postMetaData['formType'] === self::FORM_TYPE_DATETIME) {

					if (isset($_POST[$postMetaKey . '-date'])) {
						$valueDate = sanitize_text_field($_POST[$postMetaKey . '-date']);
					} else {
						$valueDate = '';
					}

					if (isset($_POST[$postMetaKey . '-time'])) {
						$valueTime = sanitize_text_field($_POST[$postMetaKey . '-time']);
					} else {
						$valueTime = '';
					}

					$value = $valueDate . ' ' . $valueTime;

				} else if ($postMetaData['formType'] === self::FORM_TYPE_COLOR_SELECT) {
					$value = sanitize_hex_color($_POST[$postMetaKey]);
				} else {
					if(isset($_POST[$postMetaKey])){
						if( isset($postMetaData['dataType'] ) && $postMetaData['dataType'] === 'URL') {
							$value = esc_url($_POST[$postMetaKey]);
						} else {
							$value = sanitize_text_field($_POST[$postMetaKey]) ;
						}
					} else {
						$value = '';
					}


					if ($value && $postMetaData['name'] === self::ITEM_NAME_IDS) {
						$intIds = $this->convertIntArr( $value );
						$intIds = array_unique($intIds);
						$value  = implode(', ', $intIds);
					}
				}

				$new_datas[$postMetaKey] = $value;
				update_post_meta($post_id, $postMetaKey, $value);
			}

			$endAtKey = $this->__ap(self::ITEM_NAME_END);
			$isCacheDataDelete = false;
			if (isset($new_datas[$endAtKey]) && $new_datas[$endAtKey]) {
				$datetime = current_datetime();
				$currentDate = $datetime->format( 'YmdHi' ) ;
				if (date('YmdHi', strtotime($new_datas[$endAtKey])) > $currentDate) {
					$isCacheDataDelete = true;
				}
			}

			$meta_datas=$new_datas;
			//キャッシュにいれる
			set_transient($this->__ap(self::DB_META_SAVE_KEY . '_' . $post_id), $meta_datas, self::EXPIRED_TIME);

			// 一般アクセス用のキャッシュを削除する
			if ($isCacheDataDelete) {
				$this->deleteCacheData();
			}
		}
	}



	// </editor-fold>


	// <editor-fold desc="一覧画面">

	public function registerPluginLinks($links, $file) {
		if($file == MCH795_CNAS_PLUGIN_BASE_NAME) {
			$links[] = '<a href="https://dev.macha795.com/wp-plugin-notice-manage/">' . $this->_t('プラグインのサイトで説明を見る') . '</a>';
		}
		return $links;
	}


	public function manager_list_custom_filter($views ){
		return $views;
	}


	/**
	 * style管理一覧の表示カラム
	 * @param $columns
	 * @return mixed
	 */
	public function manager_list_columns($columns ) {
		$date = $columns['date'];
		unset($columns['date']);
		unset($columns['slug']);
		unset($columns['word-count']);
		unset($columns['pv']);
		unset($columns['thumbnail']); //アイキャッチ

		$columns['content']    = $this->_t('メッセージ');
		$columns['url']        = $this->_t('URL');
		$columns['noticeType'] = $this->_t('通知タイプ');
		$columns['colors']     = $this->_t('色');
		$columns['targetType'] = $this->_t('指定方法');
		$columns['ids']        = $this->_t('指定ID');
		$columns['startAt']    = $this->_t('開始日時');
		$columns['endAt']      = $this->_t('終了日時');


		$columns['date'] = $date;
		return $columns;
	}


	/**
	 *  一覧の情報表示
	 * @param $column_name
	 * @param $post_id
	 */
	function manager_list_column_value($column_name, $post_id ) {
		/** @var WP_Post $post */
		global $post;
		if (
			$column_name == self::ITEM_NAME_IDS
			|| $column_name == 'noticeType'
		) {
			$data = $this->getCustomPostMetaData($post_id, $column_name);
			echo esc_attr( sanitize_text_field($data) );
		}
		else if ($column_name == 'url') {
			$data = $this->getCustomPostMetaData($post_id, $column_name);
			echo esc_attr( $data );
		}
		else if ($column_name == 'content') {
			echo strip_tags($post->post_content);
		}
		else if (
			$column_name == 'colors'
		) {
			$txtColor = $this->getCustomPostMetaData($post_id, self::ITEM_NAME_TXT_COLOR);
			$bgColor = $this->getCustomPostMetaData($post_id, self::ITEM_NAME_BG_COLOR);
			echo $this->htmlColorBox($bgColor, $txtColor);
		} else if (
		   $column_name == self::ITEM_NAME_START
		|| $column_name == self::ITEM_NAME_END
		) {
			$data = $this->getCustomPostMetaData($post_id, $column_name);
			if ($column_name == self::ITEM_NAME_START) {
				$startDateTime = date('YmdHi', strtotime(esc_attr(sanitize_text_field($data))));
				$endDateTime   = date('YmdHi', strtotime(esc_attr(sanitize_text_field($this->getCustomPostMetaData($post_id, self::ITEM_NAME_END)))));
			}
			else if ($column_name == self::ITEM_NAME_END) {
				$startDateTime = date('YmdHi', strtotime(esc_attr(sanitize_text_field($this->getCustomPostMetaData($post_id, self::ITEM_NAME_START)))));
				$endDateTime   = date('YmdHi', strtotime(esc_attr(sanitize_text_field($data))));
			}

			echo $this->htmlStartEndAt(esc_attr(sanitize_text_field($data)), $startDateTime, $endDateTime);
		}
		else if (
			$column_name == self::ITEM_NAME_TARGET_TYPE
		) {
			$data = (int)$this->getCustomPostMetaData($post_id, $column_name);
			$str  = self::NOTICE_TARGET_TYPE_LIST[$data];
			echo esc_attr($str);
		}
	}



	/**
	 * @param $bgColor
	 * @param $txtColor
	 * @return string
	 */
	private function htmlColorBox($bgColor, $txtColor){
		$cssBgColor  = "";
		$cssTxtColor = "";
		$bgColor     = esc_attr( sanitize_hex_color($bgColor) );
		$txtColor    = esc_attr( sanitize_hex_color($txtColor) );

		if ($bgColor) {
			$cssBgColor = "background-color: {$bgColor};";
		} else {
			$bgColor = $this->_t('背景色なし');
		}
		if ($txtColor) {
			$cssTxtColor = "color: {$txtColor};";
		} else {
			$txtColor = $this->_t('文字色なし');
		}

		return <<<TEXT
<div style="{$cssBgColor} padding: 5px; {$cssTxtColor}; border-radius: 5px; ">{$bgColor} <div
 style="border: solid;border-radius: 8px; display: inline-block; padding: 0 10px; margin: 0 10px;"
 >{$txtColor}</div> </div>
TEXT;
	}


	/**
	 * @param $dateStr
	 * @param $startDateTime
	 * @param $endDateTime
	 * @return string
	 */
	private function htmlStartEndAt($dateStr, $startDateTime, $endDateTime){
//		$currentDate = date('YmdHi');
		$datetime = current_datetime();
		$currentDate = $datetime->format( 'YmdHi' ) ;
		$colorCss    = '';
		if ($startDateTime <= $currentDate && $currentDate <= $endDateTime) {
			$colorCss = 'background-color : #beffca ;';
		}

		return <<<TEXT
<div style="{$colorCss} padding: 8px 5px;border-radius: 5px; ">{$dateStr}</div>
TEXT;
	}




	/**
	 * 管理一覧、ソート対象カラム指定
	 * @param $columns
	 * @return mixed
	 */
	function manager_list_manage_sortable($columns) {
		$columns[self::ITEM_NAME_TARGET_TYPE] = self::ITEM_NAME_TARGET_TYPE;
		$columns[self::ITEM_NAME_START]       = self::ITEM_NAME_START;
		$columns[self::ITEM_NAME_END]         = self::ITEM_NAME_END;
		return $columns;
	}


	/**
	 * 管理一覧ソート設定
	 * @param $vars
	 * @return array
	 */
	function manager_list_order_setting($vars) {
		return $vars;
	}


	/**
	 * 設定チェック
	 */
	public function displaySettingCheckResult(){
		global $post_type;
		if( !isset( $post_type ) || self::APP_POST_TYPE !== $post_type ){
			return;
		}
		if (!$this->isCocoonTheme()) {
			$msg = $this->_t( 'テーマが Cocoon ではありません。' );
			echo <<<TEXT
<div class="notice notice-warning">
	<h3 class="title">{$msg}</h3>
</div>
TEXT;
		}

		if ($this->isCocoonTheme() && !$this->isCocoonNoticeAreaVisible()) {
			$msg = $this->_t( 'Cocoon の通知エリアの表示が無効になっています。' );
			echo <<<TEXT
<div class="notice notice-warning">
	<h3 class="title">{$msg}</h3>
</div>
TEXT;
		}
	}


	// </editor-fold>




}