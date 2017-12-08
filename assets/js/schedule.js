$(document).ready(function() {
	jQuery.curCSS = function(element, prop, val) {
	    return jQuery(element).css(prop, val);
	};
	
	$(document).on('click', '#open-menu', function (e) {
        e.preventDefault();
        
        $('#left-side-menu').slideToggle('medium');
    });
	
	$('.date-picker').datepicker({
		format:'m/d/Y',
		onSelect: function(formated) {
			var date = formated.split('/').join('-');
			if ($("#m-schedule-item-" + date).length) {
				$('.m-schedule-list').animate({
					scrollTop: $("#m-schedule-item-" + date).offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
		        }, 1000);
			} else {
				if (formated > $('.m-schedule-list-provider').attr('end')) {
					var dates = new Date(formated);
					dates.setDate(dates.getDate() + 10);
					var dd = dates.getDate();
					var mm = dates.getMonth() + 1;
					var y = dates.getFullYear();

					var end = (mm < 10 ? ('0' + mm) : mm) + '/' + (dd < 10 ? ('0' + dd) : dd) + '/'+ y;
					
					loadSchedules(1, $('.m-schedule-list-provider').attr('end'), end, formated);
				} else if (formated < $('.m-schedule-list-provider').attr('start')) {
					loadSchedules(-1, formated, $('.m-schedule-list-provider').attr('start'), formated);
				}
			}
		}
	});
    
    $('.m-schedule-list').scroll(function(){
        if ($(this)[0].scrollHeight - $(this).scrollTop() === $(this).outerHeight()) {
        	load_on_scroll(1);
        } else if ($(this).scrollTop() == 0) {
        	load_on_scroll(-1);
        }
    });
    
    $(document).on('click', '.task-note', function(e) {
    	e.preventDefault();
    	
    	$(this).parents('.m-schedule-item').find('.more-info').trigger('click');
    });
    
    $(document).on('click', '.more-info', function(e) {
    	e.preventDefault();
    	
    	var selected = $(this);
    	$('.m-schedule-list').animate({
			scrollTop: $(selected).parents('.m-schedule-item').offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
        }, 100);
    	
    	$.ajax({
    		method: "POST",
    		url: '/schedule/render_schedule_modal',
    		dataType: 'json',
    		data: {
    			requested_date: $(selected).attr('date')
    		},
    		beforeSend: function() {
    			$('#scheduleModal .modal-content').html('Loading...');
    		},
    		success: function(response) {
    			if (response.status) {
    				$('#scheduleModal .modal-content').html(response.html);
    				$('#scheduleModal').modal('show');
    				
    			} else {
    				if (response.timed_out) {
    					window.location.reload();
    				}
    				$.notify(response.message, "warn");
    			}
    		}
    	});
    });
    
    $(document).on('click', '#submit-off-request', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
    	sendRequest($(this));
    });
    
    $(document).on('click', '#cancel-off-request', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	
		if (confirm('Do you want to cancel day off request?')) {
			sendRequest($(this));
		}
    });
    
    $(document).on('click', '#add-note', function(e) {
    	e.preventDefault();
    	
    	if ($(this).hasClass('disabled')) {
    		return false;
    	}
    	if ($.trim($('#scheduleModal .txt-request-note').val()) == '') {
    		$.notify('Please add note.', "warn");
    		return false;
    	}
		
		var selected = $(this);
		
		$.ajax({
    		method: "POST",
    		url: '/schedule/add_note',
    		dataType: 'json',
    		data: {
    			requested_date: $('#scheduleModal .request_date').val(),
    			request_note: $.trim($('#scheduleModal .txt-request-note').val())
    		},
    		beforeSend: function() {
    			$(selected).addClass('disabled').html('<img src="/assets/img/loading.gif" atl="Please wait..."/>');
    			$('#cancel-close').addClass('disabled');
    		},
    		success: function(response) {
    			if (response.status) {
    				$.notify('Note has been added.', "success");
    				$('#scheduleModal .comment-list').append(response.new_note);
    				$('#scheduleModal .txt-request-note').val('').css("height", "40");
    				if ($('#' + $(selected).attr('for-item')).find('.task-note').length <= 0) {
    					$('<a href="#" class="task-note">Notes</a>').insertAfter($('#' + $(selected).attr('for-item') + ' .m-task-list'));
    				}    				
    			} else {
    				if (response.timed_out) {
    					window.location.reload();
    				}
    				$.notify(response.message, "warn");
    			}
    		},
    		complete: function() {
    			$(selected).removeClass('disabled').html('Submit');
    			$('#cancel-close').removeClass('disabled');
    		}
    	});
    });
    
    $(document).on('focus', '.txt-request-note', function() {
    	$(this).css("height", "+=50");
    });
});

function sendRequest(selected)
{
	$.ajax({
		method: "POST",
		url: '/schedule/request_day_off',
		dataType: 'json',
		data: {
			requested_date: $('.request_date').val()
		},
		beforeSend: function() {
			$(selected).addClass('disabled').html('<img src="/assets/img/loading.gif" atl="Please wait..."/>');
			$('#cancel-close').addClass('disabled');
		},
		success: function(response) {
			if (response.status) {
				$.notify(response.message, "success");
				
				if ($(selected).hasClass('pending')) {
    				$('#scheduleModal .btn-request').addClass('request').removeClass('pending').html('Request Day Off').attr('id', 'submit-off-request');
    				$('#' + $(selected).attr('for-item') + ' .m-task-list .task-list-item-request').remove();
				} else {
    				$('#scheduleModal .btn-request').removeClass('request').addClass('pending').html('Request Off Pending').attr('id', 'cancel-off-request');
    				$('#' + $(selected).attr('for-item') + ' .m-task-list').append('<li class="task-list-item task-list-item-request"><span>Day Off request Pending</span></li>');
				}
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		},
		complete: function() {
			$(selected).removeClass('disabled');
			$('#cancel-close').removeClass('disabled');
		}
	});
}

function loadSchedules(direction, start, end, scrollto)
{
	if ($('.m-schedule-list-provider').find('.loading').length > 0) {
		return false;
	}
	$.ajax({
		method: "POST",
		url: '/schedule/load_schedules',
		dataType: 'json',
		data: {
			direction: direction,
			start: start,
			end: end,
		},
		beforeSend: function() {
			if (direction == 1) {
				$('.m-schedule-list-provider').append('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			} else {
				$('.m-schedule-list-provider').prepend('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			}
		},
		success: function(response) {
			if (response.status) {
				if (direction == 1) {
					$('.m-schedule-list-provider').append(response.html);
				} else {
				    //need to hold scroll position when prepending without jumping to the top
                    //get current hight 
                    
                    var hbefore = $('.m-schedule-list')[0].scrollHeight;
                    $('.m-schedule-list-provider').prepend(response.html);
                    var hafter = $('.m-schedule-list')[0].scrollHeight;
                    var hloading = $('.m-schedule-list .loading').height();
                    $('.m-schedule-list').scrollTop(hafter-hbefore-hloading-9);
                    
				}
				if (response.start) {
					$('.m-schedule-list-provider').attr('start', response.start);
				}
				if (response.end) {
					$('.m-schedule-list-provider').attr('end', response.end);
				}
				
				if (scrollto) {
					var date = scrollto.split('/').join('-');
					if ($("#m-schedule-item-" + date).length) {
						$('.m-schedule-list').animate({
							scrollTop: $("#m-schedule-item-" + date).offset().top - $('.m-schedule-list').offset().top + $('.m-schedule-list').scrollTop()
				        }, 1000);
					}
				}
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		},
		complete: function() {
			$('.m-schedule-list-provider').find('.loading').remove();
		}
	});
}

function load_on_scroll(direction) {
    var start = direction == 1 ? addDays($('.m-schedule-list .m-schedule-list-provider').attr('end'), 1) : '';
	var end = direction == -1 ? addDays($('.m-schedule-list .m-schedule-list-provider').attr('start'),-1) : '';
	
	loadSchedules(direction, start, end, null);
}

function addDays(date, days) {
    var result = new Date(date);
    result.setDate(result.getDate() + days);
    var dd = result.getDate();
    var mm = result.getMonth()+1; //January is 0!

        var yyyy = result.getFullYear();
        if(dd<10){
            dd='0'+dd;
        } 
        if(mm<10){
            mm='0'+mm;
        } 
        var result = mm+'/'+dd+'/'+yyyy;
    
    return result;
}