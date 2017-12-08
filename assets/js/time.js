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
			if ($("#m-time-item-" + date).length) {
				$('.m-time-list').animate({
					scrollTop: $("#m-time-item-" + date).offset().top - $('.m-time-list').offset().top + $('.m-time-list').scrollTop()
		        }, 1000);
			} else {
				if (formated > $('.m-time-list-provider').attr('end')) {
					var dates = new Date(formated);
					dates.setDate(dates.getDate() + 10);
					var dd = dates.getDate();
					var mm = dates.getMonth() + 1;
					var y = dates.getFullYear();

					var end = (mm < 10 ? ('0' + mm) : mm) + '/' + (dd < 10 ? ('0' + dd) : dd) + '/'+ y;
					
					load_times(1, $('.m-time-list-provider').attr('end'), end, formated);
				} else if (formated < $('.m-time-list-provider').attr('start')) {
					load_times(-1, formated, $('.m-time-list-provider').attr('start'), formated);
				}
			}
		}
	});
    
    $('.m-time-list').scroll(function(){
        if ($(this)[0].scrollHeight - $(this).scrollTop() === $(this).outerHeight()) {
        	//load_on_scroll(1);
        } else if ($(this).scrollTop() == 0) {
        	load_on_scroll(-1);
        }
    });
});


function load_times(direction, start, end, scrollto)
{
	if ($('.m-time-list-provider').find('.loading').length > 0) {
		return false;
	}
	$.ajax({
		method: "POST",
		url: '/time/load_times',
		dataType: 'json',
		data: {
			direction: direction,
			start: start,
			end: end,
		},
		beforeSend: function() {
			if (direction == 1) {
				$('.m-time-list-provider').append('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			} else {
				$('.m-time-list-provider').prepend('<li class="loading"><img src="/assets/img/loading.gif" alt="please wait ..."/></li>');
			}
		},
		success: function(response) {
			if (response.status) {
				if (direction == 1) {
					$('.m-time-list-provider').append(response.html);
				} else {
					$('.m-time-list-provider').prepend(response.html);
				}
				if (response.start) {
					$('.m-time-list-provider').attr('start', response.start);
				}
				if (response.end) {
					$('.m-time-list-provider').attr('end', response.end);
				}
				
				if (scrollto) {
					var date = scrollto.split('/').join('-');
					if ($("#m-time-item-" + date).length) {
						$('.m-time-list').animate({
							scrollTop: $("#m-time-item-" + date).offset().top - $('.m-time-list').offset().top + $('.m-time-list').scrollTop()
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
			$('.m-time-list-provider').find('.loading').remove();
		}
	});
}

function load_on_scroll(direction) {
	var start = direction == 1 ? $('.m-time-list .m-time-list-provider').attr('end') : '';
	var end = direction == -1 ? $('.m-time-list .m-time-list-provider').attr('start') : '';
	
	load_times(direction, start, end, null);
}