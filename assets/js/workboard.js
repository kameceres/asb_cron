// Declaring class "Workboard"
var Workboard = function(options) {
	// Timer element html class
	this.taskClass = options.taskClass;
	this.worker_id = options.worker_id;
	
    // Member variable: Hold instance of this class
    var thisObj;
	
	this.loadingImg = '<img src="/assets/img/loading.gif" />';
	this.mainClockOut = '.main-clock-out';
	this.mainClockIn = '.main-clock-in';
	
	this.loadSidebarUrl = '/ajax/load_sidebar';
	this.saveManualTimeUrl = '/ajax/save_manual_time';
	this.logoutUrl = '/logout';
	
	this.clock_in = options.clock_in;
	this.hour = parseInt(options.hour);
	this.minute = parseInt(options.minute);
	this.second = parseInt(options.second);
    this.sessionTimer = parseInt(options.sessionTimer);
    this.countdown = parseInt(options.countdown);
    this.enable = false;
    
    this.timeFormat = options.timeFormat;
    
    // Interval to send alive request
    this.aliveInterval = 30;
    this.sessionTimeout = options.sessionTimeout;
    this.aliveTimer = 0;
    
    this.enable_clock_in = options.enable_clock_in;
    this.enable_start_task = options.enable_start_task;
    
    this.tasks = [];
    
    // Function: Init object
    this.init = function() {
    	thisObj = this;
    	
    	thisObj.bodyEvents();
    	
    	thisObj.clock();
    	
    	thisObj.manualTime();
    	
    	// Init all tasks with status = diabled
    	thisObj.initTasks();
    	
    	if (!thisObj.enable_clock_in) {
    		$('.btn-main-clock-in').addClass('disabled');
    		$('.btn-main-clock-out').addClass('disabled');
    	}
    	if (!thisObj.enable_start_task) {
    		$('.btn-start-task').addClass('disabled');
    		$('.btn-stop-task').addClass('disabled');
    	}
    	
    	thisObj.updateTranslation();
		
    	// start main clock timer
    	setInterval(function() {
    		thisObj.tick();
    	}, 1000);

		// Update Tasks
		setInterval(function () {
			thisObj.updateTasks();
		}, this.aliveInterval * 1000);
    	
    	if (thisObj.clock_in) {
    		thisObj.clockedIn(options);
    	}
    	
    	thisObj.loadSideBar();
    };
    
    this.bodyEvents = function() {
    	$(".time-picker").timepicker({timeFormat: thisObj.timeFormat});
    	
    	$('body').on('mousemove', function() {
    		thisObj.resetAliveTimer();
    	});
    	$('body').on('click', function() {
    		thisObj.resetAliveTimer();
    	});
    	$('body').on('keypress', function() {
    		thisObj.resetAliveTimer();
    	});
    	$('body').on('scroll', function() {
    		thisObj.resetAliveTimer();
    	});
    };
    
    this.updateTranslation = function() {
    	$.ajax({
			method: "POST",
			url: '/ajax/update_translation',
			dataType: 'json',
			data: {
				department_id: $('.btn-department.btn-success').attr('department-id')
			},
			success: function(response) {
				for (x in response.tasks) {
					$('.' + x).html(response.tasks[x]);
				}
				if ($('.daily-notes').length) {
					$('.daily-notes .note-content').html(response.daily_notes);
				}
			}
		});
    };
    
    /**
     * Init all tasks with status = diabled
     * After clicking CLOCK IN, tasks will be set to enabled
     */
    this.initTasks = function() {
    	$('.' + thisObj.taskClass).each(function() {
    		var task = new Task({
				elementId: $(this).attr('id'),
				time_id: $.trim($(this).attr('time_id')),
				hour: $.trim($(this).attr('data-hour')),
				minute: $.trim($(this).attr('data-minute')),
				second: $.trim($(this).attr('data-second')),
				sessionTimer: $.trim($(this).attr('data-timer')),
				mobile: options.mobile,
				workboard: thisObj
    		});
    		task.init();
    		thisObj.tasks.push(task);
    	});
    };

    this.updateTasks = function() {
        $.ajax({
            url: '/ajax/check_tasks?load_item_html=1&all_jobs=1',
            success: function (res) {
                res = JSON.parse(res);
                if (res.reload == 1) {
					window.location.reload();
				}

                if (typeof res.tasks == 'object') {
                    //Task list was changed
                    if (Object.keys(res.tasks).length != Object.keys(thisObj.tasks).length) {
                        //window.location.reload();
                    } else {
                        $.each(res.tasks, function (tIndex, task) {
                            var itemSelector = '.' + thisObj.taskClass + ':eq(' + tIndex + ')';

                            if (typeof task.show_job != 'undefined' && !task.show_job) {//Hide
                                $(itemSelector).addClass('hide');
                            } else {//Show
                                $(itemSelector).removeClass('hide');
                            }
                            
                            if (task.grayed_out) {
                            	$(itemSelector).addClass('grayed-out');
                            } else {
                            	$(itemSelector).removeClass('grayed-out');
                            }
                        });

                        $('.' + thisObj.taskClass + ':visible').each(function(i, el) {
                            $(this).find('.job-index-num').text(i + 1);
                        });
                    }
                }
            }
        });
    };
    
    /**
     * Render sidebar section
     */
    this.loadSideBar = function() {
    	$.ajax({
			method: "POST",
			url: thisObj.loadSidebarUrl,
			dataType: 'json',
			beforeSend: function() {
				$(".past-times").html(thisObj.loadingImg);
				$(".schedules").html(thisObj.loadingImg);
				$(".todays-times").html(thisObj.loadingImg);
			},
			success: function(response) {
				$(".past-times").html(response.pasttimes_html);
				$(".schedules").html(response.schedules_html);
				$(".todays-times").html(response.todays_html);
			},
			error: function() {
				setTimeout(function(){ thisObj.loadSideBar(); }, 10*1000);
			}
		});
    };
    
    this.resetAliveTimer = function() {
    	if (!$('#aliveModal').hasClass('showing')) {
    		thisObj.aliveTimer = 0;
    	}
    };

    /**
     * Count up by minute and display time
     */
    this.tick = function() {
    	thisObj.sessionTimer++;
    	thisObj.aliveTimer++;
    	
    	if (thisObj.enable) {
    		if (thisObj.second == 59) {
    			if (thisObj.minute == 59) {
            		thisObj.minute = 0;
            		thisObj.hour++;
            	} else {
            		thisObj.minute++;
            	}
    			thisObj.second = 0;
    		} else {
    			thisObj.second++;
    		}
    		
    		// Send alive request
        	/*if (thisObj.sessionTimer % thisObj.aliveInterval == 0) {
        		thisObj.working();
        	}*/
    	}
    	
    	thisObj.show();
    	
    	if (thisObj.aliveTimer > thisObj.sessionTimeout) {
    		window.location = thisObj.logoutUrl;
    		
    	} else if (thisObj.aliveTimer >= thisObj.sessionTimeout - 30) {
    		$('#aliveModal .modal-title').html('Your will be logged out in ' + (thisObj.sessionTimeout - thisObj.aliveTimer) + ' secs. Do you want to stay logged in.');
    		if (thisObj.aliveTimer == thisObj.sessionTimeout - 30) {
	    		$('#aliveModal').addClass('showing').modal('show');
	    	}
    	}
    	
    	if (thisObj.countdown <= 0 && thisObj.enable) {
    		$('.' + options.mainClockoutButton).trigger('click');
    		window.location.reload();
    	} else {
        	thisObj.countdown--;
    	}
    };
    
    /**
     * Click CLOCK IN button
     */
    this.clock = function() {
    	$(document).on('click', '.' + options.mainClockInButton, function(e) {
    		e.preventDefault();
    		
    		var selected = $(this);
    		if ($(selected).hasClass('disabled')) {
    			return false;
    		}
    		
    		$.ajax({
    			method: "POST",
    			url: options.mainClockInUrl,
    			data: {
    				mobile: options.mobile
    			},
    			dataType: 'json',
    			beforeSend: function() {
    				$(selected).html(thisObj.loadingImg).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					thisObj.clockedIn(response);
    				} else {
    					if (response.reload) {
    						window.location.reload();
    					}
    					$(selected).html('CLOCK IN');
    					$.notify(response.message, "error");
    				}
    			},
    			error: function() {
    				$.notify("There was an issue why trying to clock in, please try again", "error");
    				$(selected).html('CLOCK IN');
    			},
    			complete: function() {
    				$(selected).removeClass('disabled');
    				thisObj.resetAliveTimer();
    			}
    		});
    	});
    	

    	$(document).on('click', '.' + options.mainClockoutButton, function(e) {
    		e.preventDefault();
    		var selected = $(this);
    		
    		$.ajax({
    			method: "POST",
    			url: options.mainClockOutUrl,
    			dataType: 'json',
    			data: {
    				mobile: options.mobile
    			},
    			beforeSend: function() {
    				$(selected).html(thisObj.loadingImg).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					thisObj.clockedOut(response);
    				} else {
    					if (response.reload) {
    						window.location.reload();
    					}
    					$(selected).html('CLOCK OUT');
    					$.notify(response.message, "error");
    				}
    			},
    			error: function() {
    				$.notify("There was an issue while trying to clock out, please try again", "error");
    				$(selected).html('CLOCK OUT');
    			},
    			complete: function() {
    				$(selected).removeClass('disabled');
    				thisObj.resetAliveTimer();
    			}
    		});
    	});
    };
    
    /**
     * Set workboard status after clicking clock out
     */
    this.clockedIn = function(data) {
		// Init main clock timer
		thisObj.enable = true;
		thisObj.hour = data.hour;
		thisObj.minute = data.minute;
		thisObj.sessionTimer = data.sessionTimer ? data.sessionTimer : data.second;
    	thisObj.countdown = data.countdown;
    	
    	// Change CLOCK IN button to CLOCK OUT
    	if (thisObj.enable_clock_in) {
    		$('.' + options.mainClockInButton).removeClass(options.mainClockInButton).addClass(options.mainClockoutButton).html('CLOCK OUT');
    	} else {
    		$('.' + options.mainClockInButton).removeClass(options.mainClockInButton).addClass(options.mainClockoutButton).html('CLOCKED IN');
    	}
		
		$(thisObj.mainClockIn).html(data.clock_in);
		thisObj.show();
		
    	// All tasks can be started now
		if (thisObj.enable_start_task) {
			/*for (let s of thisObj.tasks) {
				s.setEnabled(true);
			}*/
			
			for (s in thisObj.tasks) {
				thisObj.tasks[s].setEnabled(true);
			};
    	}
    };
    
    /**
     * Set workboard status after clicking clock out
     */
    this.clockedOut = function(data) {
    	// Change CLOCK OUT button to CLOCK IN
		$('.' + options.mainClockoutButton).removeClass(options.mainClockoutButton).addClass(options.mainClockInButton).html('CLOCK IN');
		
    	// Can not start any task
    	/*for (let s of thisObj.tasks) {
			s.setEnabled(false);
		}*/
    	for (s in thisObj.tasks) {
			thisObj.tasks[s].setEnabled(false);
		};
		
		// Reset timer
		thisObj.enable = false;
		thisObj.hour = data.hour;
		thisObj.minute = data.minute;
		thisObj.sessionTimer = 0;
		$(thisObj.mainClockIn).html('--:--');
		thisObj.show();
		
		//thisObj.loadSideBar();
    };
    
    /**
     * Send request to server: update on_working
     */
    this.working = function() {
    	if (thisObj.enable == false) {
    		return false;
    	}
    	
    	// Send ajax request to notify server: on working
    	$.ajax({
			method: "POST",
			url: options.workingUrl,
			dataType: 'json',
			data: {
				worker_id: thisObj.worker_id,
				mobile: options.mobile
			},
			success: function(response) {
				if (response.reload == 1) {
					window.location.reload();
				}
			}
		});
    };
    
    this.show = function() {
    	$('.main-timer').html(thisObj.hour + ':' + (thisObj.minute < 10 ? '0' + thisObj.minute : thisObj.minute) + ':' + (thisObj.second < 10 ? '0' + thisObj.second : thisObj.second));
    };
    
    this.strToTime = function(strTime) {
    	var parts = strTime.split(' ');
    	if (!parts[0]) {
    		return false;
    	}
    	
    	var times = parts[0].split(':');
    	if (!times[0]) {
    		return false;
    	}
    	
    	if (parts.length == 2) {
    		if (parts[1] == 'PM' || parts[1] == 'pm') {
    			times[0] = '' + (parseInt(times[0]) + 12);
    		}
    		if (parts[1] == 'AM' || parts[1] == 'am') {
    			times[0] = times[0] == '12' ? '00' : times[0];
    		}
    	}
    	
    	var currentDate = new Date()
    	currentDate.setHours(times[0] ? parseInt(times[0]) : 0);
    	currentDate.setMinutes(times[1] ? parseInt(times[1]) : 0);
    	currentDate.setSeconds(times[2] ? parseInt(times[2]) : 0);
		return currentDate.getTime();
    }
    
    this.timeToString = function(intTime) {
    	var dateObj = new Date(intTime);
		var hours = dateObj.getHours();
		var minutes = dateObj.getMinutes();
		minutes = minutes < 10 ? '0' + minutes : '' + minutes;
		var seconds = dateObj.getSeconds();
		seconds = seconds < 10 ? '0' + seconds : '' + seconds;
		if (thisObj.timeFormat == 'H:i:s') {
			return (hours < 10 ? '0' + hours : hours) + ':' + minutes + ':' + seconds;
		} else {
			var apm = 'AM';
			if (hours == 0) {
				hours = 12;
			}
			if (hours > 12) {
				hours = hours - 12;
				apm = 'PM';
			}
			return (hours < 10 ? '0' + hours : hours) + ':' + minutes + ':' + seconds + ' ' + apm;
		}
    }
    
    this.manualTime = function() {
    	$(document).on('click', '.open-modal', function(e) {
    		e.preventDefault();
    		
    		$($(this).attr('data-target')).find('.wb_task_id').val($(this).attr('wb_task_id'));
    		$($(this).attr('data-target')).modal('show');
    	});
    	
    	$(document).on('changeTime', '.manual-start_time', function() {
    		var start = thisObj.strToTime($.trim($('.manual-start_time').val()));
    		
    		if ($.trim($('.manual-end_time').val()) != '') {
    			// Calculate number of hours from start_time and end_time	
    			var end = thisObj.strToTime($.trim($('.manual-end_time').val()));
    			
    			var seconds = Math.round((end - start)/ 1000);
    			$('.manual-hours').val(seconds > 0 ? seconds/3600 : 0)
    			
    		} else {
    			if ($.trim($('.manual-hours').val()) != '') {
    				// Calculate end_time from start_time and number of hours
    				var end = start + parseFloat($.trim($('.manual-hours').val())) * 3600 * 1000;
    				
    				$('.manual-end_time').val(thisObj.timeToString(end));
    			}
    		}
    	});
    	
    	$(document).on('change', '.manual-end_time', function() {
    		var end = thisObj.strToTime($.trim($('.manual-end_time').val()));
    		
    		if ($.trim($('.manual-start_time').val()) != '') {
    			// Calculate number of hours from start_time and end_time
    			var start = thisObj.strToTime($.trim($('.manual-start_time').val()));
    			
    			var seconds = Math.round((end - start)/ 1000);
    			$('.manual-hours').val(seconds > 0 ? seconds/3600 : 0)
    		} else {
    			if ($.trim($('.manual-hours').val()) != '') {
    				// Calculate start_time from end_time and number of hours
    				var start = end - parseFloat($.trim($('.manual-hours').val())) * 3600 * 1000;
    				
    				$('.manual-start_time').val(thisObj.timeToString(start));
    			}
    		}
    	});

		$(document).on('change', '.manual-hours', function() {
			if ($.trim($('.manual-start_time').val()) != '') {
				// Calculate end_time from start_time and number of hours
				var start = thisObj.strToTime($.trim($('.manual-start_time').val()));
				var end = start + parseFloat($.trim($('.manual-hours').val())) * 3600 * 1000;
				
				$('.manual-end_time').val(thisObj.timeToString(end));
    		} else {
    			if ($.trim($('.manual-end_time').val()) != '') {
    				// Calculate end_time from start_time and number of hours
    				var end = thisObj.strToTime($.trim($('.manual-end_time').val()));
    				var start = end - parseFloat($.trim($('.manual-hours').val())) * 3600 * 1000;
    				
    				$('.manual-start_time').val(thisObj.timeToString(start));
        		}
    		}
		});
		
		$(document).on('keypress', '.manual-start_time, .manual-end_time, .manual-hours', function(e) {
			if (e.which == 13) {
				$('.btn-save-manual-time').trigger('click');
				
				return false;
			}
		});
    	
    	$(document).on('click', '.btn-save-manual-time', function(e) {
    		e.preventDefault();
    		
    		var selected = $(this);
    		if ($(selected).hasClass('disabled')) {
    			return false;
    		}
    		
    		$.ajax({
    			method: "POST",
    			url: thisObj.saveManualTimeUrl,
    			dataType: 'json',
    			data: $(".manual-time-form").serialize(),
    			beforeSend: function() {
    				$(selected).html(thisObj.loadingImg).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					window.location.reload();
    				} else {
    					if (response.timed_out) {
    						window.location.reload();
    					}
    					var dismiss = '<button type="button" class="close close-manual-error"><span aria-hidden="true">&times;</span></button>';
    					$('#manual-time-error').removeClass('hide').html(dismiss + response.message);
    				}
    			},
    			error: function() {
    				$.notify("There was an issue while trying to add manual time, please try again", "error");
    			},
    			complete: function() {
    				$(selected).html('Save').removeClass('disabled');
    				thisObj.resetAliveTimer();
    			}
    		});
    	});
    	
    	$(document).on('click', '.close-manual-error', function(e) {
    		e.preventDefault();
    		
    		$(this).parent().html("").addClass('hide');
    	});
    	
    	$(document).on('click', '#aliveModal .btn-success', function(e) {
    		e.preventDefault();
    		
    		thisObj.working();
    		
    		$('#aliveModal').removeClass('showing').modal('hide');
    		
    		thisObj.resetAliveTimer();
    	});
    };
};