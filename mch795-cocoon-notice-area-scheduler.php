<?php
/*
Plugin Name: CocoonNoticeAreaScheduler
Plugin URI:  https://dev.macha795.com/wp-plugin-notice-manage/
Description: WordPressテーマ「Cocoon」で動作するプラグインです。Cocoonの通知エリアの設定を複数設定でき、表示スケジュールを管理できます。
Author: macha795
Author URI:  https://macha795.com/
Text Domain: mch795-cocoon-notice-area-scheduler
Version: 0.1.0
*/


if ( !defined( 'ABSPATH' ) ) {
	exit;
}



define( 'MCH795_CNAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCH795_CNAS_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
require_once (MCH795_CNAS_PLUGIN_DIR . 'mch795_cnas_main.php');



if ( function_exists( 'add_action' ) && class_exists('Mch795_Cocoon_Notice_Area_Scheduler') ) {
	add_action( 'plugins_loaded', array('Mch795_Cocoon_Notice_Area_Scheduler', 'get_object' ) );
	add_action( 'plugins_loaded', ['Mch795_Cocoon_Notice_Area_Scheduler', 'myplugin_load_textdomain'] );
}

