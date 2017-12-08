<?php if ($past_times) { ?>
    <?php foreach ($past_times as $past_time) { ?>
        <p><?= date_by_timezone($past_time->start_time, 'Y-m-d') ?> : 
            <span>
                <?= date_format_by_timezone($past_time->start_time, 'h:i A') ?> -
                <?= date_format_by_timezone($past_time->end_time, 'h:i A') ?>
    		</span>
		</p>
    <?php } ?>
<?php } ?>