jQuery(function($) {
	console.log('test');
	$('#index-start').click(function() {
		hmp_index(0,50,0);
	});

	function hmp_index(offset = 0,qty = 50,max = 0) {

		var remaining = offset + '/' + max;
		$('#ajax-results').html(remaining);

		var path = "/admin/config/hmp_elastic/index/" + offset + '/' + qty;
		
		$.ajax({
		  url: path,
		})
		  .done(function( results ) {
		  	data = JSON.parse(results);
		  	data = data[0];
			console.log(data);
		  	if(data.status == 1) {
		  		$('#ajax-results').html('We\'re done here.');
		  	}else {
		  		if(isNaN(data.offset)) {
		  			console.log(data);
		  		} else {
				    setTimeout(function() {
				    	hmp_index(data.offset,qty,data.max);
				    },500);
				}
			}
		});
	}

});


