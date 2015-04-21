<?php ?>
<table class="form-table">
		    <tr>
				<td class="team_meta_box_td" colspan="6">
					<label for="consequences"><?php _e( 'Consequences', 'quote-post-type' ); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="6">
					<input style="width:100%;height:5em" type="textarea" id="consequences" name="consequences" value="<?php echo $risk->consequences; ?>"/>
				</td>
			</tr>
		    
		    <tr>
				<td class="team_meta_box_td" colspan="6">
					<label for="causes"><?php _e( 'Causes', 'quote-post-type' ); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="6">
					<input style="width:100%;height:5em" type="textarea" id="causes" name="causes" value="<?php echo $risk->causes; ?>"/>
				</td>
			</tr>
			<tr>
				<td class="team_meta_box_td" colspan="2">
					<label for="riskowner"><?php _e( 'Risk Owner', 'quote-post-type' ); ?>
					</label>
				</td>
				<td colspan="4">
					<select name="riskowner" id="riskowner">
				      <option>Slower</option>
				      <option>Slow</option>
				      <option selected="selected">Medium</option>
				      <option>Fast</option>
				      <option>Faster</option>
				    </select>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						try {
						jQuery('#riskowner').selectmenu();
						} catch (e){
							alert (e.message);
						}
					});
					</script>
				</td>
			</tr>
			<tr>
				<td class="team_meta_box_td" colspan="2">
					<label for="riskmanager"><?php _e( 'Risk Manager', 'quote-post-type' ); ?>
					</label>
				</td>
				<td colspan="4">
					<select name="riskmanager" id="riskmanager">
				      <option>Slower</option>
				      <option>Slow</option>
				      <option selected="selected">Medium</option>
				      <option>Fast</option>
				      <option>Faster</option>
				    </select>

				</td>
			</tr>
	</table>		