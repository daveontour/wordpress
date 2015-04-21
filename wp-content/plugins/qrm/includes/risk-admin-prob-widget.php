<?php ?>
<table class="form-table">
		    <tr>
				<td class="team_meta_box_td" colspan="2">
					<label for="test">Test
					</label>
				</td>
				<td colspan="2">
					<div style="width:180px;height:180px" id="svgDIV"></div>
					<script type="text/javascript">
					jQuery(document).ready(function() {
					   setMatrix("111222333444555", 5, 5, [1,2,3,4,5,6,7,8,9], "#svgDIV", false)
					});
					</script>
				</td>
				<td colspan="2">
					<div style="width:180px;height:180px" id="svgDIV2"></div>
					<script type="text/javascript">
					jQuery(document).ready(function() {
					   setMatrix("111222333444555", 5, 5, [1,2,3,4,5,6,7,8,9], "#svgDIV2", false)
					});
					</script>
				</td>
			</tr>
</table>