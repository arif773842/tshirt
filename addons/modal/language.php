<?php
 /* Date updated: June 2019, 25 */
$settings = $GLOBALS['settings'];

if (isset($GLOBALS['languages']) && isset($GLOBALS['languages']) && setValue($settings, 'show_languages', 1) == 1) {
	$addons = $GLOBALS['addons'];
	$languages = $GLOBALS['languages'];
	$lang_default = $GLOBALS['lang_default'];

	$uri = $_SERVER["REQUEST_URI"];
	$temp = explode('tshirtecommerce/', $uri);

	if (count($languages) > 0) {
		if (strpos($_SERVER["REQUEST_URI"], '?') > 0) {
			$link_index = $settings->site_url.'tshirtecommerce/'.$temp[1].'&';
		} else {
			$link_index = $settings->site_url.'tshirtecommerce/'.$temp[1].'?';
		}

		$temp = explode('lang=', $link_index);
		$link_index = $temp[0];
?>
	<div class="modal fade languages-modal" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php echo $addons->__('addon_languag_title_'.$lang_default->code); ?></h4>
				</div>
				<div class="modal-body">
					<?php foreach ($languages as $language) {
						if (isset($language->published) && $language->published == 0) {
							continue;
						} ?>
						<div class="list-group">
							<a class="list-group-item" href="<?php echo $link_index; ?>lang=<?php echo $language->code; ?>">
								<div class="row">
									<div class="col-xs-7">
										<span class="lang-title"><?php echo $language->title; ?></span>
									</div>
									<div class="col-xs-3">
										<span class="pull-right">
											<img src="addons/images/<?php echo $language->code; ?>.png" title="<?php echo $language->title; ?>" />
										</span>
									</div>
									<div class="col-xs-2">
										<?php if ($language->code == $lang_default->code) { ?>
										<i class="fa fa-check fa-2x" aria-hidden="true"></i>
										<?php } ?>
									</div>
								</div>
							</a>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
<?php } ?>
