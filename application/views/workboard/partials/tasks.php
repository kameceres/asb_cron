<div class="table-responsive task-table">
    <div>
    <div class="row-title">
        <div class="column task-name width55"><?= $this->lang->line('task') ?></div>
        <div class="column act-time width15">Time</div>
        <div class="column work-flow width15"><?= $this->lang->line('action') ?></div>
        <div class="column task-more width15"><?= $this->lang->line('more') ?></div>
    </div>
    </div>
    <div>
    	<?php foreach ($tasks as $index => $task): ?>
        	<?php
                $show_job = !isset($task->show_job) || 1 == $task->show_job;
        	    $timer = $task->start_time ? ($task->end_time ? $task->end_time : time()) - $task->start_time : 0;
        	    $total_time = $task->total_time + $timer;
        	    $hour = floor(intval($total_time) / 3600);
        	    $minute = floor((intval($total_time) % 3600) / 60);
        	    $second = intval($total_time) - $hour * 3600 - $minute * 60;
        	    $task_name = $task->task_name;
        	    if ($task->wb_task_notes) {
        	        $task_name .= ' - ' . $task->wb_task_notes;
        	    }
        	    if ($task->task_notes) {
        	        $task_name .= ' - ' . $task->task_notes;
        	    }
        	    
        	    $notes = '';
        	    if ($need_translate) {
        	        if ($task->task_translation) {
        	            $notes = $task->task_translation;
        	        }
        	        if ($task->wb_task_notes_tran) {
        	            $notes .= ' - ' . $task->wb_task_notes_tran;
        	        }
        	        if ($task->trans_note) {
        	            $notes .= ' - ' . $task->trans_note;
        	        }
        	    }
        	?>
            <div class="task-item <?= ($active_sb && $active_sb != $task->split_table_id) ? 'grayed-out' : '' ?> <?= $task->show_job ? '' : 'hide'; ?>" id="task-<?= $index ?>"
            	data-hour="<?= $hour ?>" data-minute="<?= $minute ?>" data-second="<?= $second ?>" time_id="<?= $task->time_id ?>" data-timer="<?= $timer ?>">
                <div class="column task-name width55">
                    <p style="font-size: 20px;">
                    	<?= $task_name ?>
                    </p>
                    <span class="hide job-notes-<?= $task->wb_task_id ?>" style="font-weight: normal; font-size: 15px;">
                        <?= $notes ?>
                    </span>
                    
                    <div class="split-name"><?= $task->split_name ?></div>
                    
                   
                	<?php if ($task->equipments): ?>
                	<div class="equipment-wrapper">
                	<?php foreach ($task->equipments as $equipment): ?>
                		<span class="equipment-item"><?php echo $equipment->equipment_model . ' ' . $equipment->equipment_model_id; ?></span><br>
                	<?php endforeach;?>
                	</div>
                	<?php endif; ?>
                </div>
                <div class="column act-time over-est width15">
                    <p class="est-time"><?= $task->true_est_hr ?></p>
                    <p class="act-timer">
                    	<?= !$task->time_id ? $hour . ':' . ($minute < 10 ? '0' . $minute : $minute) . ':' . ($second < 10 ? '0' . $second : $second) : '' ?>
                    </p>
                </div>
                <div class="column work-flow width15">
                	<?php if ($task->time_id): ?>
                    	<a href="#" class="btn btn-stop-task" wb_task_id="<?= $task->wb_task_id ?>">STOP</a>
                    <?php else: ?>
                    	<a href="#" class="btn btn-start-task disabled" wb_task_id="<?= $task->wb_task_id ?>">START</a>
                    <?php endif; ?>
                </div>
                <div class="column task-more width15">
                    <div class="tool">
                        <div class="tool-item">
                            <a href="#" class="open-modal" data-target="#note-modal" title="Add task notes" wb_task_id="<?= $task->wb_task_id ?>">
                                <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="tool-item">
                            <a href="#" class="open-modal" data-target="#manual-time-modal" title="Add manual time" wb_task_id="<?= $task->wb_task_id ?>">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bottom-content clearfix">
    <button class="btn btn-default btn-add-task"><?= $this->lang->line('add') ?> <?= $this->lang->line('task') ?></button>
</div>