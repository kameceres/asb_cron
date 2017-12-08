<div class="col-xs-12 col-sm-4 col-lg-3 employee-menu">
    <div class="employee-clock">
        <form class="form-horizontal">
            <div class="form-group mar0 clock">
            	<h2 style="margin-top: 0px;"><?= date('Y-m-d') ?></h2>
                <a href="#" class="btn btn-main-clock-in"><?= $this->lang->line('clock_in') ?></a>
            </div>
        </form>
    </div>
    <div class="menu-line"></div>
    <div class="panel-group sidebar-panel" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingOne">
                <h3 class="panel-title">
                    <a role="button" class="employee-title" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <?= $this->lang->line('todays_times') ?> <span class="fa fa-chevron-circle-down" aria-hidden="true"></span>
                    </a>
                </h3>
            </div>
            <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
                <div class="panel-body">
                    <div class="employee-today">
                        <div class="row mar0">
                            <div class="col-xs-4 col-sm-12 col-md-4 pad0">
                                <div class="time-title">
                                    <p><?= $this->lang->line('in') ?></p>
                                    <p class="main-clock-in">--:--</p>
                                </div>
                            </div>
                            <div class="col-xs-4 col-sm-12 col-md-4 pad0">
                                <div class="time-title">
                                    <p><?= $this->lang->line('time') ?></p>
                                    <p class="main-timer">--:--</p>
                                </div>
                            </div>
                            <div class="col-xs-4 col-sm-12 col-md-4 pad0">
                                <div class="time-title">
                                    <p><?= $this->lang->line('out') ?></p>
                                    <p class="main-clock-out">--:--</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="todays-times">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="menu-line"></div>
        <div class="panel panel-default mobile-hide">
            <div class="panel-heading" role="tab" id="headingTwo">
                <h3 class="panel-title">
                    <a role="button" class="employee-title" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <?= $this->lang->line('past_times') ?> <span class="fa fa-chevron-circle-down" aria-hidden="true"></span>
                    </a>
                </h3>
            </div>
            <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                <div class="panel-body">
                    <div class="employee-timeline past-times"></div>
                </div>
            </div>
        </div>
        
        <div class="menu-line mobile-hide"></div>
        <div class="panel panel-default mobile-hide">
            <div class="panel-heading" role="tab" id="headingThree">
                <h3 class="panel-title">
                    <a class="employee-title" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        <?= $this->lang->line('schedules') ?> <span class="fa fa-chevron-circle-down" aria-hidden="true"></span>
                    </a>
                </h3>
            </div>
            <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                <div class="panel-body">
                    <div class="employee-timeline schedules"></div>
                </div>
            </div>
        </div>
    </div>
</div>