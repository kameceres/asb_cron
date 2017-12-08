<?php foreach ($schedules as $date => $schedule): ?>
<div class="row employee-timeline-item">
    <div class="col-xs-6 date">
    	<?php $dayofweek = date_format_by_timezone(strtotime($date), 'l'); ?>
    	<?php echo date_format_by_timezone(strtotime($date), 'n/j'); ?> - <span class="<?php echo ($dayofweek == 'Sunday' || $dayofweek == 'Saturday') ? 'day-off' : ''?>"><?php echo $dayofweek; ?></span>
    </div>
    <div class="col-xs-6 date-schedule">
    	<?php if (isset($schedule['schedules']) && is_array($schedule['schedules'])) { ?>
    		<?php foreach ($schedule['schedules'] as $item) { ?>
    			<?php if ($item->day_off != 1) { ?>
    				<p class="date-schedule-time"><?php echo date_format_by_timezone($item->start_time, 'g:ia'); ?> - <?php echo date_format_by_timezone($item->end_time, 'g:ia'); ?></p>
    			<?php } else { ?>
    				<p class="date-schedule-time day-off"><?php echo $this->lang->line('off'); ?></p>
    			<?php } ?>
    		<?php } ?>
    	<?php } else { ?>
    		<?php if (isset($schedule['tasks']) && $schedule['tasks'] == 1) { ?>
    			<p class="date-schedule-time"><?php echo $this->lang->line('working'); ?></p>
    		<?php } else { ?>
    			<p class="date-schedule-time day-off"><?php echo $this->lang->line('no_schedule'); ?></p>
    		<?php } ?>
    	<?php } ?>
    </div>
</div>
<?php endforeach; ?>