<div class="modal fade" id="missing-punch-modal" tabindex="-1" role="dialog" aria-labelledby="missing-punch-modal-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="missing-punch-modal-label"><?= $this->lang->line('update') ?> <?= $this->lang->line('missing_punch') ?></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal missing-punch-form">
                	<div class="form-group mar0">
                		<div class="alert alert-danger hide" id="missing-punch-error"></div>
                	</div>
                    <div class="form-group mar0">
                    	
                    	<div class="">
                    		<h4>Working Session in <?= $working_session->working_date ?></h4>
                    		<div class="col-xs-12">
                    			<input type="hidden" name="working_session_id" value="<?= $working_session->working_session_id ?>" />
                            	<div class="col-xs-4 pad0">
                                	<label>Clock In: &nbsp;&nbsp; <?= date_format_by_timezone($working_session->start_time, 'h:i:s A') ?></label>
                                </div>
                                <div class="col-xs-6 pad0">
                                	<div class="col-xs-4 pad0"><label>Clock Out</label></div>
                                	<div class="col-xs-6 pad0"><input type="text" name="working_session_end_time" class="time-picker form-control manual-end_time"></div>
                                </div>
                            </div>
                            
                            <?php if ($working_task): ?>
                            <div class="col-xs-12">
                                <h5><?= $working_task->task_name ?></h5>
                                <input type="hidden" name="time_keeping_id" class="time_keeping_id" value="<?= $working_task->time_id ?>"/>
                            	<div class="col-xs-4 pad0">
                                	<label>Clock In: &nbsp;&nbsp; <?= date_format_by_timezone($working_task->start_time, 'h:i:s A') ?></label>
                                </div>
                                <div class="col-xs-6 pad0">
                                	<div class="col-xs-4 pad0"><label>Clock Out</label></div>
                                	<div class="col-xs-6 pad0"><input type="text" name="time_keeping_end_time" class="time-picker form-control manual-end_time"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group text-right note-button">
                    	<button type="button" class="btn btn-warning btn-skip-missing-punch">Skip</button>
                        <button type="button" class="btn btn-success btn-save-missing-punch">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>