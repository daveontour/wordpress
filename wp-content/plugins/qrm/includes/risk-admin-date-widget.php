<?php ?>
<table class="form-table">
			<tr>
				<td class="team_meta_box_td" colspan="2">
					<label for="start_date"><?php _e( 'Start of Exposure', 'quote-post-type' ); ?>
					</label>
				</td>
				<td colspan="4">
					<input type="text" id="StartDate" name="start_date" value="<?php echo $risk->startDate; ?>"/>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('#StartDate').datepicker({
							dateFormat : 'dd-mm-yy'
						});
					});
					</script>
				</td>
			</tr>
			<tr>
				<td class="team_meta_box_td" colspan="2">
					<label for="end_date"><?php _e( 'End of Exposure', 'quote-post-type' ); ?>
					</label>
				</td>
				<td colspan="4">
					<input type="text" id="EndDate" name="end_date" value="<?php echo $risk->endDate; ?>"/>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('#EndDate').datepicker({
							dateFormat : 'dd-mm-yy'
						});
					});
					</script>
				</td>
			</tr>
</table>