<div id="main" class="container main-content pad0">
    <div class="row mar0">

        <!-- Left Sidebar-->
        <?php $this->load->view("workboard/partials/sidebar");?>

        <!--Main Content-->
        <div class="col-xs-12 col-sm-8 xol-lg-9 content">
            <div class="main-content-header">
                <div class="header-item header-left">
                    <h2 class="page-title"><?= $this->session->userdata('worker_name') ?></h2>
                </div>
                <div class="header-item header-right hide">
                    <ul class="header-list">
                        <li>
                            <a href="#" title="Shows PPE">
                                
                                <span class="fa fa-life-ring" aria-hidden="true" width="40"></span>
                                
                            </a>
                        </li>
                        <li style="width: auto">
                            <div class="btn-group">
                                <button class="btn btn-default" title="Week View"><span class="fa fa-calendar-o" aria-hidden="true"></span></button>
                                <button class="btn btn-default" title="Month View"><span class="fa fa-calendar" aria-hidden="true"></span></button>
                            </div>
                        </li>
                        <li class="active">
                            <a href="#" title="Today Task">
                                <span class="fa fa-list" aria-hidden="true"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="list-task">
                <?php $this->load->view("workboard/partials/tasks");?>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="manual-time-modal" tabindex="-1" role="dialog" aria-labelledby="manual-time-modal-label">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="manual-time-modal-label">Add Manual Time</h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal manual-time-form">
                	<div class="form-group mar0">
                		<div class="alert alert-danger hide" id="manual-time-error"></div>
                	</div>
                	<input type="hidden" name="mobile" value="0" />
                	
                	<?php if ($logged_times): ?>
                	<div class="form-group mar0" style="padding-bottom: 10px;">
                		<div class="row">
                        	<div class="col-xs-12">
                        		<label>Please notice logged time:</label>
                				<?= $logged_times ?>
                			</div>
                		</div>
                	</div>
                	<?php endif; ?>
                	
                    <div class="form-group mar0">
                    	<input type="hidden" name="wb_task_id" class="wb_task_id" />
                    	<div class="row">
                        	<div class="col-xs-4">
                            	<label>Start</label>
                                <input type="text" name="start_time" class="time-picker form-control manual-start_time">
                            </div>
                            <div class="col-xs-4">
                                <label>End</label>
                                <input type="text" name="end_time" class="time-picker form-control manual-end_time">
                            </div>
                        	<div class="col-xs-4">
                            	<label>Number of Hours</label>
                                <input type="text" name="number_of_hours" class="form-control manual-hours" placeholder="Format: 2 or 2.5">
                            </div>
                        </div>
                        
                        <!--
                        <br/>
                        <div class="row">
                            <div class="col-xs-12">
                                <label>Note</label>
                                <textarea id="task-note" name="note" cols="2" class="form-control  manual-note"></textarea>
                            </div>
                        </div>
                        -->
                    </div>
                    <div class="form-group text-right note-button">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success btn-save-manual-time">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="aliveModal" tabindex="-1" role="dialog" aria-labelledby="aliveModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="aliveModalLabel">Your will be logged out in 30 secs. Do you want to stay logged in?</h4>
            </div>
            <div class="modal-body">
                <div class="form-group text-right note-button">
                    <a type="button" class="btn btn-danger" href="/logout">Log Out</a>
                    <button type="button" class="btn btn-success">Stay Logged In</button>
                </div>
            </div>
        </div>
    </div>
</div>