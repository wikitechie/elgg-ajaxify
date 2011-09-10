elgg.provide('elgg.ajaxify.likes');
elgg.ajaxify.likes.action = function(item) {
	var entityGUID = elgg.ajaxify.getGUIDFromMenuItem(item);
	var actionURL = elgg.ajaxify.getURLFromMenuItem(item);
	elgg.action(actionURL, {
		success: function() {
			var riverItem = $(item).parent().parent().parent().parent();
			var riverItemId = riverItem.attr('id').match(/item-river-(\d+)/)[1];
			elgg.view('river/getitem', {
				data: {
					'id' : riverItemId
				}, 
				target: $(riverItem),
			});
		}
	});
};
