<?php
/** @var Mch795_Cocoon_Notice_Area_Scheduler $cnasMain */
$cnasMain = $this;
$post_id = $post->ID;
if (isset($_GET['copy_id']) && $_GET['copy_id']) {
	$post_id = (int)$_GET['copy_id'];
}
?>

<div id="mch_input_main">
	<div class="<?= $cnasMain::APP_PREFIX ?>_form_item">
		<label class="<?= $cnasMain::APP_PREFIX ?>_form_item_name"><?php echo $cnasMain->_t( '通知メッセージ' ) ?></label>
		<div>
			<textarea class="" id="content" name="content"><?php echo esc_textarea( $post->post_content ); ?></textarea>
		</div>
	</div>

	<?php
	foreach ($cnasMain->getNoticeAreaInfo() as $formItem) {
		$formName       = $formItem['name'];
		$formPrefixName = $cnasMain->__ap( $formName );
		$formTitle      = $formItem['title'];
		$afterLabel     = '';
		$afterTipsLabel = '';
		if (isset($formItem['afterTipsLabel'])) {
			$afterTipsLabel = $formItem['afterTipsLabel'];
		}
		if (isset($formItem['afterLabel'])) {
			$afterLabel = $formItem['afterLabel'];
		}
		$metaData = $cnasMain->getCustomPostMetaData( $post_id, $formName );
		?>
		<div class="<?= $cnasMain::APP_PREFIX ?>_form_item">
			<label class="<?= $cnasMain::APP_PREFIX ?>_form_item_name"><?php echo $cnasMain->_t( $formTitle ) ?></label>
			<div>
		<?php if($formItem['formType'] === $cnasMain::FORM_TYPE_TEXT) {  ?>
			<?php
			if( isset($formItem['dataType'] ) && $formItem['dataType'] === 'URL') {
				$value = esc_attr($metaData);
			} else {
				$value = esc_attr( sanitize_text_field($metaData) );
			}

			?>
			<input type="<?= $cnasMain::FORM_TYPE_TEXT ?>"
			       name="<?= $formPrefixName ?>"
			       id="<?= $formPrefixName ?>" class=""
			       value="<?php echo $value; ?>"
			>
		<?php } else if($formItem['formType'] === $cnasMain::FORM_TYPE_CHECKBOX) {  ?>

			<label><input type="checkbox"
			       name="<?= $formPrefixName ?>"
			       value="1"<?php $cnasMain->the_checkbox_checked( esc_attr( sanitize_text_field($metaData) ) ); ?>><?php echo $cnasMain->_t( $afterLabel ); ?></label>

		<?php } else if($formItem['formType'] === $cnasMain::FORM_TYPE_LIST) {  ?>

			<select name="<?= $formPrefixName ?>">
				<?php foreach ( $formItem['list'] as $value => $str ){ ?>
				<option value="<?php echo $value ?>" <?php $cnasMain->the_select_selected( esc_attr( sanitize_text_field($metaData) ), $value ); ?> ><?php echo $cnasMain->_t( $str ) ?></option>
				<?php } ?>
			</select>

		<?php } else if($formItem['formType'] === $cnasMain::FORM_TYPE_DATETIME) {  ?>
			<?php
			$date = date('Y-m-d', strtotime('-2 day'));
			if ($formName === 'startAt') {
				$time = '00:00';
			}
			else if ($formName === 'endAt') {
				$time = '23:59';
			}
			else {
				$time = date('H:i');
			}

			if ($metaData) {
				$date = date('Y-m-d', strtotime(esc_attr( sanitize_text_field($metaData) )));
				$time = date('H:i',   strtotime(esc_attr( sanitize_text_field($metaData) )));
			}
			?>
			<input type="date" id="<?= $formPrefixName ?>-date" name="<?= $formPrefixName ?>-date" value="<?php echo $date; ?>" /><input name="<?= $formPrefixName ?>-time" type="time" value="<?php echo $time; ?>" /><?php echo $cnasMain->_t( $afterLabel ); ?>

			<?php
			if ($formName === 'endAt') {
			?>
				｜<span class="button dateUpdateBtn" data-target-id="<?= $formPrefixName ?>-date" data-update-val="<?php echo date('Y-m-d', strtotime('100 years')) ?>"><?php echo $cnasMain->_t( '半永久的に表示' ) ?></span>
			<?php } ?>

		<?php } else if($formItem['formType'] === $cnasMain::FORM_TYPE_COLOR_SELECT) {  ?>
			<input type="text" name="<?= $formPrefixName ?>" value="<?php echo esc_attr(sanitize_hex_color($metaData)); ?>" >
			<?php
			$cnasMain->generate_color_picker_tag($formPrefixName);
			?>
		<?php } ?>
			<?php
			if ($afterTipsLabel) {
				$cnasMain->generate_tips_tag($afterTipsLabel);
			}
			?>
		</div>
</div>
		<?php
	}
	?>
</div>

<?php
$formItemKey = $cnasMain->__ap( $cnasMain::FORM_KEY_FROM_PAGE);
?>
<input id="<?php echo $formItemKey ?>" type="hidden" name="<?php echo $formItemKey ?>" value="main"/>


<style>
</style>

<script type="text/javascript">
    (function ($) {
        $(document).ready(function(){
        	$(document).on('click', '.dateUpdateBtn', function(e){
				var targetId  = $(this).data('target-id');
				var updateVal = $(this).data('update-val');
				$('#' + targetId).val(updateVal);
			});

        });

    })(jQuery);

</script>

