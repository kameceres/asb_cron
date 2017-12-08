$(document).ready(function() {
    $(document).on('click', '.safety-expire', function() {
        $(this).parents('.m-safety-wrapper-item').find('.safety-expire-list').slideToggle('medium');
    });
    
    $(document).on('click', '.safety-active', function() {
        $(this).parents('.m-safety-wrapper-item').find('.safety-active-list').slideToggle('medium');
    });

    $(document).on('click', '#open-menu', function (e) {
        e.preventDefault();

        $('#left-side-menu').slideToggle('medium');
    });
			    
    $(document).on('click', '.load-more', function(e) {
    	e.preventDefault();
    	var selected = $(this);
    	
    	$.ajax({
			method: "POST",
			url: 'safety/load_more',
			dataType: 'json',
			data: {
				offset: $(selected).attr('offset'),
				active: $(selected).hasClass('load-more-expired') ? 0 : 1
			},
			beforeSend: function() {
				$(selected).html('<img src="/assets/img/loading.gif" alt="please wait ..."/>');
			},
			success: function(data) {
				if (data.status) {
					$(selected).parents('.m-safety-body').find('.m-safety-list').append(data.html);
					if (parseInt($(selected).attr('offset')) + data.count >= parseInt($(selected).parents('.m-safety-body').attr('total-count'))) {
						$(selected).addClass('hide');
					}
					$(selected).attr('offset', parseInt($(selected).attr('offset')) + data.count);
				} else {
					if (data.reload) {
						window.location.reload();
					}
				}
			},
			error: function() {
				$(selected).html('Load more');
				$.notify("There was an error when trying to load more data, please try again!", "error");
			}
		});
    });
});