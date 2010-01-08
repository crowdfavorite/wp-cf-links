(function($) {

// Links manager object
	window.cflk = {};		
		
	cflk.opts = {
		ajax_url:ajaxurl, // ajaxurl is predefined in the admin by WordPress
		views:{} // empty placeholder for item view states for inserting new items, filled by ajax calls
	};
		
	cflk.init = function() {
		this.links_list = $('#cflk-list-sortable');
		this.link_item_forms = $('#cflk-edit-forms-wrapper');
		this.link_item_new_button = $('#cflk-new-list-item');
		this.no_items_list_item = $('#cflk-list-sortable .cflk-no-items');
	};

// Editing helper
	// cflk.editing_items = {
	// 	link_id:null,
	// 	link_type:null
	// };
	// 
	// cflk.editing = function(params) {
	// 	if (params === 0) {
	// 		// reset
	// 		for (i in this.editing_items) {
	// 			this.editing_items[i] = null;
	// 		}
	// 	}
	// 	else {
	// 		// add to
	// 		for (i in params) {
	// 			this.editing_items[i] = params[i];
	// 		}
	// 	}
	// 	return true;
	// };

// Unique Name/Slug check
	cflk.getNewListId = function() {
		var list_name = $('#cflk-list-name').val();
		
		if (list_name.length) {
			data = {
				action:'cflk_ajax',
				func:'check_unique_list_id',
				args:JSON.stringify({ name:$('#cflk-list-name').val() })
			};
		
			$.post(
				this.opts.ajax_url,
				data,
				function(r, textStatus) {
					if (textStatus == 'success') {
						if (r.success) {
							cflk.insertNewListId(r);
						}
						else {
							cflk.error('There was an error processing this request');
						}
					}
					else {
						cflk.error('There was an error contacting the server.');
					}
				},
				'json'
			);
		}
	};
	
	cflk.insertNewListId = function(data) {
		$('#cflk-list-id').val(data.list_id);
	};
	
// Add/Edit Links	

	// show new link form
	cflk.newLink = function() {
		this.link_item_forms.show();
		this.link_item_new_button.hide();
	};

	// cancel new link form
	cflk.cancelNewLink = function() {
		this.link_item_forms.hide();
		this.link_item_new_button.show();
	};
	
	// send data to server to get display state of link item
	cflk.processNewLink = function() {
		var form_data = $('.cflk-type-forms li:visible :input', this.link_item_forms).serialize();		
		var data = {
			action:'cflk_ajax',
			func:'get_link_view',
			args:JSON.stringify({form_data:form_data})
		};
		
		$.post(
			this.opts.ajax_url,
			data,
			function(r, statusText) {
				if (statusText == 'success') {
					if (r.success) {
						cflk.insertNewLink(r);
					}
					else {
						cflk.error('There was an error processing the request');
					}
				}
				else {
					cflk.error('There was an error contacting the server.');
				}
			},
			'json'
		);
	};
	
	cflk.insertNewLink = function(data) {
		if (this.no_items_list_item.is(':visible')) {
			this.no_items_list_item.hide();
		}
		this.links_list.append($('<li />').html(data.html));
	};
		
	cflk.editLink = function() {
		
	};
	
	cflk.linkItemForms = function() {
		// return a clone of the edit forms
		return $('.cfkl_edit_forms', this.link_item_forms).clone();
	};
	
	cflk.selectType = function() {
		_this = $(this);
		$('#cflk-type-' + _this.val(), _this.parents('div.cflk-type-select').siblings('.cflk-type-forms'))
			.css({'display':'block'})
			.siblings()
			.css({'display':'none'});
	};

// Error Handling
	cflk.error = function(message) {
		alert(message);
	};
	
// Init
	$(function(){
		// Init links object
			cflk.init();
		
		// TR hovers
			$('#cflk-available-lists tr').hover(
				function() {
					$('.cflk-showhide', $(this)).css({'visibility':'visible'});
				},
				function() {
					$('.cflk-showhide', $(this)).css({'visibility':'hidden'});
				}
			);

		// generic togglr
			$('.cflk-toggle').click(function() {
				$($(this).attr('href')).slideToggle();
				return false;
			});

		// New Link Actions
			$('#cflk-new-list-item').click(function() {
				cflk.newLink();
				return false;
			});
		
			$('#cflk-list-items-footer .cflk-link-edit-done').click(function() {
				cflk.processNewLink();
				return false;
			});
		
			$('#cflk-list-items-footer .cflk-cancel').click(function() {
				cflk.cancelNewLink();
				return false;
			});

		// Item type select
			$('select[name="cflk-types"]').live('change', cflk.selectType);

		// Unique list slug setting
			if ($('#cflk-list-name').empty()) {
				$('#cflk-list-name').blur(function() {
					cflk.getNewListId();
				});
			}
		});
	
})(jQuery);