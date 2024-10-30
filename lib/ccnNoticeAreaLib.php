<?php




//通知エリアメッセージ
//if ( !function_exists( 'get_notice_area_message' ) ):
function get_notice_area_message(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeMessage();
}
//endif;


//通知エリアURL
//if ( !function_exists( 'get_notice_area_url' ) ):
function get_notice_area_url(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeUrl();
}
//endif;



//if ( !function_exists( 'get_notice_area_background_color' ) ):
function get_notice_area_background_color(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeBackColor();
}
//endif;


//if ( !function_exists( 'get_notice_area_text_color' ) ):
function get_notice_area_text_color(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeTextColor();
}
//endif;



//if ( !function_exists( 'is_notice_link_target_blank' ) ):
function is_notice_link_target_blank(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeLinkTargetBlank();
}
//endif;


function get_notice_type(){
	$cnasMain = Mch795_Cocoon_Notice_Area_Scheduler::get_object();
	return $cnasMain->getNoticeTypeStr();
//	return get_theme_option(OP_NOTICE_TYPE);
}