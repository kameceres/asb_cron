// Declaring class "MissingPunch"
var MissingPunch = function(options) {
	
    // Member variable: Hold instance of this class
    var thisObj;
    
    this.saveMissingPunchUrl = '/ajax/save_missing_punch';
    this.skipMissingPunchUrl = '/ajax/skip_missing_punch';
    
    // Function: Init object
    this.init = function() {
    	thisObj = this;
    	
    	$("#missing-punch-modal").modal({backdrop: "static", keyboard: false});
    	
        $('input[name="working_session_end_time"]').timepicker({timeFormat:  options.timeFormat})
        .on('changeTime', function() {
            if ($('input[name="time_keeping_end_time"]').val() == '') {
            	$('input[name="time_keeping_end_time"]').val($(this).val());
            } else {
            	if (thisObj.compareTimeStrings($(this).val(), $('input[name="time_keeping_end_time"]').val()) < 0) {
            		$('input[name="time_keeping_end_time"]').val($(this).val());
            	}
            }
        });
        
        $('input[name="time_keeping_end_time"]').timepicker({timeFormat:  options.timeFormat})
        .on('changeTime', function() {
            if ($('input[name="working_session_end_time"]').val() == '') {
            	$('input[name="working_session_end_time"]').val($(this).val());
            } else {
            	if (thisObj.compareTimeStrings($('input[name="working_session_end_time"]').val(), $(this).val()) < 0) {
	            	if (confirm("Do you want to end working session at " + $(this).val())) {
	        			$('input[name="working_session_end_time"]').val($(this).val());
	        		}
            	}
            }
        });
        
        thisObj.saveMissingPunch();
    };
    
    this.compareTimeStrings = function(t1, t2) {
    	thisObj = this;
    	
    	var d1 = thisObj.createDateTimeObject(t1.split(" "));
		var d2 = thisObj.createDateTimeObject(t2.split(" "));
		
		if (d1.getTime() < d2.getTime()) {
			return -1;
		} else if (d1.getTime() == d2.getTime()) {
			return -0;
		} else {
			return 1;
		}
    }
    
    this.createDateTimeObject = function(arrTime) {
    	var tmp = arrTime[0].split(":");
		if (arrTime.length == 2) {
			if (arrTime[1].toLowerCase() == 'am' && tmp[0] == 12) {
				tmp[0] = 0;
			}
			if (arrTime[1].toLowerCase() == 'pm' && tmp[0] != 12) {
				tmp[0] = tmp[0] + 12;
			}
		}
		var d = new Date();
		d.setHours(tmp[0]);
		d.setMinutes(tmp[1]);
		d.setSeconds(tmp[2]);
		
		return d;
    }
    
    this.saveMissingPunch = function() {
    	$(document).on('click', '.btn-skip-missing-punch', function(e) {
    		e.preventDefault();
    		
    		var selected = $(this);
    		if ($(selected).hasClass('disabled')) {
    			return false;
    		}
    		
    		$.ajax({
    			method: "POST",
    			url: thisObj.skipMissingPunchUrl,
    			dataType: 'json',
    			data: $(".missing-punch-form").serialize(),
    			beforeSend: function() {
    				$(selected).html(thisObj.loadingImg).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					window.location.reload();
    				} else {
    					if (response.reload) {
    						window.location.reload();
    					}
    					if (response.message) {
    						var dismiss = '<button type="button" class="close close-missing-punch-error"><span aria-hidden="true">&times;</span></button>';
        					$('#missing-punch-error').removeClass('hide').html(dismiss + response.message);
    					}
    				}
    			},
    			error: function() {
    				$.notify("There was an issue while trying to update missing punch, please try again", "error");
    			},
    			complete: function() {
    				$(selected).html('Save').removeClass('disabled');
    			}
    		});
    	});
    	
    	$(document).on('click', '.btn-save-missing-punch', function(e) {
    		e.preventDefault();
    		
    		var selected = $(this);
    		if ($(selected).hasClass('disabled')) {
    			return false;
    		}
    		
    		$.ajax({
    			method: "POST",
    			url: thisObj.saveMissingPunchUrl,
    			dataType: 'json',
    			data: $(".missing-punch-form").serialize(),
    			beforeSend: function() {
    				$(selected).html(thisObj.loadingImg).addClass('disabled');
    			},
    			success: function(response) {
    				if (response.status) {
    					window.location.reload();
    				} else {
    					if (response.reload) {
    						window.location.reload();
    					}
    					if (response.message) {
    						var dismiss = '<button type="button" class="close close-missing-punch-error"><span aria-hidden="true">&times;</span></button>';
        					$('#missing-punch-error').removeClass('hide').html(dismiss + response.message);
    					}
    				}
    			},
    			error: function() {
    				$.notify("There was an issue while trying to update missing punch, please try again", "error");
    			},
    			complete: function() {
    				$(selected).html('Save').removeClass('disabled');
    			}
    		});
    	});
    	
    	$(document).on('click', '.close-missing-punch-error', function(e) {
    		e.preventDefault();
    		
    		$(this).parent().html("").addClass('hide');
    	});
    };
};