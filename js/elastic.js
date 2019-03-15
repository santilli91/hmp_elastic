jQuery(function($) {
	var page = 0;
	console.log('test');
	$('#index-start').click(function() {
		$.ajax({
			url: '/admin/config/hmp_elastic/index/delete',
		}).done(function(results) {
			hmp_index(0,50,0);
		});
	});

	$('#elastic-search-terms').keydown(function(e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			page=0;
			performSearch();
		}
	});
	$('#elastic-search-submit').click(function(e) {
		e.preventDefault();
		page=0;
		performSearch();
	});
	$(document).on('click','#elastic-pager-previous',function() {
		page--;
		performSearch();
	});
	$(document).on('click','#elastic-pager-next',function() {
		page++;
		performSearch();
	});


	function performSearch() {
		var query = $('#elastic-search-terms').val();
		var query_terms = $('#elastic-filter-terms').val();
		$.ajax({
			url: "/hmp-elastic/search/query?page=" + page + "&query=" + query + "&query_terms=" + query_terms,
		})
		.done(function(results) {
			$('#elastic-search-results').html(results);
		});
	}

	function hmp_index(offset = 0,qty = 50,max = 0) {

		var remaining = offset + '/' + max;
		$('#ajax-results').html(remaining);

		var path = "/admin/config/hmp_elastic/index/" + offset + '/' + qty;
		
		$.ajax({
		  url: path,
		})
		  .done(function( results ) {
		  	//console.log(results);
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


