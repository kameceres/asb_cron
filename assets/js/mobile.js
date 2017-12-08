$(document).ready(function() {
	var map = new google.maps.Map(document.getElementById('geonote-maps'), {
		zoom: 417,
		mapTypeId : google.maps.MapTypeId.SATELLITE,
		streetViewControl : false
    });
	
	loadTabs($('.btn-department.btn-success').attr('department-id'));
	
	$(document).on('click', '#open-menu', function (e) {
        e.preventDefault();
        
        $('#left-side-menu').slideToggle('medium');
    });
	
	$(document).on('click', '.open-tab', function(e) {
		e.preventDefault();
		
		$('.open-tab').removeClass('active');
		$('#mowing, #holes, #notes, #geonotes').slideUp('medium');
		
		$($(this).attr('href')).slideDown('medium');
		$(this).addClass('active');
	});
	
	$(document).on('click', '.close-tabs', function(e) {
		e.preventDefault();
		
		$('.open-tab').removeClass('active');
		$('#mowing, #holes, #notes, #geonotes').slideUp('medium');
	});
	
	$(document).on('click', '.iNote', function(e) {
		var selected = $(this);
		
		$('#geoNoteModal .NotePerson').html($(selected).find('.NotePerson').html());
		$('#geoNoteModal .note-text span').html($(selected).find('.noteBodyText').html());
		
		if ($(selected).find('.PhotoIcon').hasClass('hide')) {
			$('#geoNoteModal .photo-icon-item').addClass('hide');
			$('#geoNoteModal .geonote-imgs').addClass('hide');
		} else {
			$('#geoNoteModal .photo-icon-item').removeClass('hide');
			$('#geoNoteModal .geonote-imgs').removeClass('hide');
		}
		if ($(selected).find('.MapIcon').hasClass('hide')) {
			$('#geoNoteModal .map-icon-item').addClass('hide');
			$('#geoNoteModal .geonote-maps').addClass('hide');
		} else {
			$('#geoNoteModal .map-icon-item').removeClass('hide');
			$('#geoNoteModal .geonote-maps').removeClass('hide');
		}
		console.log('show geoNoteModal');
		$('#geoNoteModal').modal('show');
		
		if ($(selected).find('.noteimgs').length) {
			var imgs = '';
			$(selected).find('.noteimgs input').each(function() {
				var imageName = $(this).val();
				var hyphenLocation = imageName.indexOf('-');
				var filename = imageName.substring(0, hyphenLocation);
				
				imgs += '<div class="item ' + (imgs ? '' : 'active') + '">';
				imgs += '<img src="' + $(selected).attr('img-path') + filename + '/' + imageName + '" class="img-responsive">';
				imgs += '</div>';
			});
			$('#geoNoteModal .carousel-inner').html(imgs);
		} else {
			$('#geoNoteModal .carousel-inner').html('');
		}
		
		if ($(selected).find('.inMarkers').length) {
			var markers = 1;
			$(selected).find('.inMarkers').each(function() {
				var myLatlng = new google.maps.LatLng($(this).find('.mapLat').val(), $(this).find('.mapLng').val());
				
				var marker = new google.maps.Marker({
					position : myLatlng,
					map : map
				});
				
				if (markers == 1) {
					map.panTo(myLatlng);
				}
				markers++;
			});
		}
	});
	
	$(document).on('shown.bs.modal', '#geoNoteModal', function () {
	    google.maps.event.trigger(map, "resize");
	    
	    if (!$('#geoNoteModal .photo-icon-item').hasClass('hide')) {
	    	$('#geoNoteModal .photo-icon-item').trigger('click');
	    } else if (!$('#geoNoteModal .map-icon-item').hasClass('hide')) {
	    	$('#geoNoteModal .map-icon-item').trigger('click');
	    }
	});
	
	$(document).on('click', '#geoNoteModal .map-icon-item', function(e) {
		e.preventDefault();
		
		$('#geoNoteModal .geonote-imgs').addClass('hide');
		$('#geoNoteModal .geonote-maps').removeClass('hide');
	});
	
	$(document).on('click', '#geoNoteModal .photo-icon-item', function(e) {
		e.preventDefault();
		
		$('#geoNoteModal .geonote-imgs').removeClass('hide');
		$('#geoNoteModal .geonote-maps').addClass('hide');
	});
});

function loadTabs(department_id)
{
	$.ajax({
		method: "POST",
		url: '/ajax/load_mobile_tabs',
		dataType: 'json',
		data: {
			department_id: department_id
		},
		beforeSend: function() {
			$('.close-tabs').trigger('click');
			$('#geonotes').html('');
			$('#mowing').html('');
			$('.daily-notes .note-content').html('');
			$('.general-notes .note-content').html('');
		},
		success: function(response) {
			if (response.status) {
				$('#geonotes').html(response.geonotes);
				$('#mowing').html(response.mowing);
				$('#footer-mowing').html(response.footer_mowing);
				$('.daily-notes .note-content').html(response.daily_notes);
				$('.general-notes .note-content').html(response.general_notes);
				
			} else {
				if (response.timed_out) {
					window.location.reload();
				}
				$.notify(response.message, "warn");
			}
		}
	});
}