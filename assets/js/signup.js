$(document).ready(function() {
    $(document).on('blur', '#username', function() {
    	var selected = $(this);
    	$.ajax({
			method: "POST",
			url: '/signup/unique_username',
			dataType: 'json',
			data: {
				username: $(selected).val(),
				signup_hash: $(selected).attr('signup_hash')
			},
			beforeSend: function() {
				$(selected).removeClass('check-background');
				$('.username-error').remove();
			},
			success: function(response) {
				if (response.status) {
					$(selected).addClass('check-background');
				} else {
					$(selected).removeClass('check-background');
					$('<div class="error username-error">This username has been taken, please try another one.</div>').insertAfter($(selected));
				}
			}
		});
    })
});