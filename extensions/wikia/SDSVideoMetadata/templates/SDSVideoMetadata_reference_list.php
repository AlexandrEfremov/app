<div class="input-group <?= $type ?>">
	<label for="<?= $name ?>"><?= $labelMsg ?></label>
	<ul>
		<?php
		if ( !is_array( $list ) ) $list = array();
		  foreach ($list as $item): 
	    ?>
			<li>
				<input type="text" name="<?= $name ?>[]" id="<?= $name ?>" value="<?= htmlspecialchars($item['name']) ?>">
				<input type="hidden" name="<?= $id ?>[]" value="<?= htmlspecialchars($item['id']) ?>">
				<button class="secondary remove"><?= wfMessage('sdsvideometadata-vc-remove-item')->text() ?></button>
			</li>
		<?php endforeach; ?>
		<li>
			<input type="text" name="<?= $name ?>[]" id="<?= $name ?>">
			<button class="secondary remove hidden"><?= wfMessage('sdsvideometadata-vc-remove-item')->text() ?></button>
		</li>
	</ul>
	<button class="add secondary"><?= wfMessage('sdsvideometadata-vc-add-item')->text() ?></button>
</div>