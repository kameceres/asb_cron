//Declaring class "Timer"
var Task = function(options) {
	this.enable = false;
	
	// HTML element to apply timer
	this.elementId = options.elementId;

	this.startTaskButton = "btn-start-task";
	this.stopTaskButton = "btn-stop-task";
	this.taskStartUrl = "/ajax/start_task";
	this.taskStopUrl = "/ajax/stop_task";
	
	this.time_id = options.time_id;
	
	// Time to init timer, format H:i:s
	this.hour = options.hour ? options.hour : 0;
	this.minute = options.minute ? options.minute : 0;
	this.second = options.second ? options.second : 0;
	// Timer for this session
	this.sessionTimer = options.sessionTimer ? options.sessionTimer : 0;
	
	this.loadingImg = '<img src="/assets/img/loading.gif" alt="please wait ..."/>';
	
	this.workboard = options.workboard;
	
    // Property: Frequency of elapse event of the timer in millisecond
    this.interval = 1000;
    
    // Member variable: Hold instance of this class
    var thisObj;
    
    // Function: Start the timer
    this.init = function() {
    	thisObj = this;
    	
    	// tick per second
    	setInterval(function() {
    		thisObj.tick();
    	}, thisObj.interval);
    	
    	this.start();
    	
    	this.stop();
    };
    
    this.setEnabled = function(enable) {
    	thisObj.enable = enable;
    	
    	if (!thisObj.enable) {
    		var btn = $('#' + thisObj.elementId + ' .' + thisObj.stopTaskButton);
    		if (btn.length) {
    			var caption = $(btn).html();
        		
        		if (caption == 'STOP') {
        			$(btn).html('START')
    			} else {
    				$(btn).html(caption.replace('stop', 'play'));
    			}
        		$(btn).removeClass(thisObj.stopTaskButton).addClass(thisObj.startTaskButton);
    		}
    		
    		$('#' + thisObj.elementId + ' .' + thisObj.startTaskButton).addClass('disabled');
			thisObj.time_id = null;
			thisObj.sessionTimer = 0;
    	} else {
    		$('#' + thisObj.elementId + ' .' + thisObj.startTaskButton).removeClass('disabled');
    	}
    };
    
    // Count up by second and display time
    this.tick = function() {
    	if (thisObj.enable) {
	    	if (thisObj.time_id) {
				if (thisObj.second == 59) {
		    		thisObj.second = 0;
		    		thisObj.minute++;
		    	} else {
		    		thisObj.second++;
		    	}
		    	if (thisObj.minute == 59) {
		    		thisObj.minute = 0;
		    		thisObj.hour++;
		    	}
	    	}
	    	
	    	thisObj.sessionTimer++;
	    	thisObj.show();
    	}
    };
    
    /**
     * Click on Clock In button
     */
    this.start = function() {
    	$(document).on('click', '#' + thisObj.elementId + ' .' + thisObj.startTaskButton, function(e) {
    		e.preventDefault();
    		
    		var startButton = $(this);
    		if (thisObj.enable == false || $(startButton).hasClass('disabled') || $(startButton).parents('.task-item').hasClass('grayed-out')) {
    			return false;
    		}
    		
    		var caption = $(startButton).html();
    		
    		$.ajax({
    			method: "POST",
    			url: thisObj.taskStartUrl,
    			dataType: 'json',
    			data: {
    				wb_task_id: $(startButton).attr('wb_task_id'),
    				mobile: options.mobile
    			},
    			beforeSend: function() {
    				$(startButton).html(thisObj.loadingImg);
    				$(startButton).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					thisObj.clockedIn(response);
    					
    					$(startButton).removeClass(thisObj.startTaskButton).addClass(thisObj.stopTaskButton);
    					if (caption == 'START') {
    						$(startButton).html('STOP');
    					} else {
    						$(startButton).html(caption.replace('play', 'stop'));
    					}

                        thisObj.workboard.updateTasks();
    				} else {
    					if (response.timed_out) {
    						window.location.reload();
    					}
    					$(startButton).html(caption);
    					$.notify(response.message, "warn");
    				}
    			},
    			error: function() {
    				$(startButton).html(caption);
    				$.notify("There was an error when trying to start task, please try again!", "error");
    			},
    			complete: function() {
    				$(startButton).removeClass('disabled');
    				thisObj.workboard.resetAliveTimer();
    			}
    		});
    	});
    };
    
    this.clockedIn = function(response) {
    	thisObj.time_id = response.time_id;
		thisObj.hour = response.hour;
		thisObj.minute = response.minute;
		thisObj.second = response.second;
		thisObj.sessionTimer = 0;
    };
    
    /**
     * Click on Clock Out button
     */
    this.stop = function() {
    	$(document).on('click', '#' + thisObj.elementId + ' .' + thisObj.stopTaskButton, function(e) {
    		e.preventDefault();
    		
    		var stopButton = $(this);
    		if (thisObj.enable == false || $(stopButton).hasClass('disabled')) {
    			return false;
    		}
    		
    		var caption = $(stopButton).html();
    		
    		$.ajax({
    			method: "POST",
    			url: thisObj.taskStopUrl,
    			dataType: 'json',
    			data: {
    				wb_task_id: thisObj.time_id,
    				timer: thisObj.sessionTimer,
    				mobile: options.mobile
    			},
    			beforeSend: function() {
    				$(stopButton).html(thisObj.loadingImg);
    				$(stopButton).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					thisObj.time_id = null;
    					$(stopButton).removeClass(thisObj.stopTaskButton).addClass(thisObj.startTaskButton);
    					if (caption == 'STOP') {
    						$(stopButton).html('START');
    					} else {
    						$(stopButton).html(caption.replace('stop', 'play'));
    					}

                        thisObj.workboard.updateTasks();
    				} else {
    					if (response.timed_out) {
    						window.location.reload();
    					}
    					$(stopButton).html(caption);
    					$.notify(response.message, "warn");
    				}
    			},
    			error: function() {
    				$(stopButton).html(caption);
    				$.notify("There was an error when trying to stop task, please try again!", "error");
    			},
    			complete: function() {
    				$(stopButton).removeClass('disabled');
    				thisObj.workboard.resetAliveTimer();
    			}
    		});
    	});
    };
    
    // Show time 00:00:00
    this.show = function() {
    	$('#' + thisObj.elementId).find('.act-timer').html(
    		thisObj.hour + ':' + 
    		(thisObj.minute < 10 ? '0' + thisObj.minute : thisObj.minute) + ':' + 
    		(thisObj.second < 10 ? '0' + thisObj.second : thisObj.second)
    	);
    };
};