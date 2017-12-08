<?php if ($todays) { ?>
    <?php foreach ($todays as $item) { ?>
        <p> 
            <span>
                <?= date_format_by_timezone($item->start_time, 'h:i A') ?> -
                <?= date_format_by_timezone($item->end_time, 'h:i A') ?>
    		</span>
		</p>
    <?php } ?>
<?php } ?>