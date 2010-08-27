(function($) {

// Links manager object
	window.cflk = {};		
		
	cflk.opts = {
		ajax_url:ajaxurl, // ajaxurl is predefined in the admin by WordPress
		views:{} // empty placeholder for item view states for inserting new items, filled by ajax calls
	};
		
	cflk.editInit = function() {
		this.links_list = $('#cflk-list-sortable');
		this.new_link_form = $('#cflk-new-link-form');
		this.link_item_forms = $('#cflk-edit-forms-wrapper');
		this.link_item_new_button = $('#cflk-new-list-item');
		this.no_items_list_item = $('#cflk-list-sortable .cflk-no-items');
		this.list_details = $('#cflk-edit-list-details-display');
		this.list_details_edit = $('#cflk-edit-list-details-edit');
		
		$("#cflk-list-sortable").sortable({
			handle : ".handle",
			update : function() {
				$("input#cflk-log").val($("#cflk-list-sortable").sortable("serialize"));
			},
			containment: "document",
			opacity: 0.5
			// stop: cflk_levels_refactor
		});
	};

// Unique Name/Slug check

	cflk.getNewListId = function() {
		var list_name = $('#cflk_list_name').val();
		
		if (list_name.length) {
			data = {
				action:'cflk_ajax',
				func:'check_unique_list_id',
				args:JSON.stringify({ name:$('#cflk_list_name').val() })
			};
		
			$.post(
				this.opts.ajax_url,
				data,
				function(r, textStatus) {
					if (r.success) {
						cflk.insertNewListId(r);
					}
					else {
						cflk.error(r.message);
					}
				},
				'json'
			);
		}
	};
	
	cflk.insertNewListId = function(data) {
		$('#cflk_list_key').val(data.list_id);
	};
	
// Add Links	

	// show new link form
	cflk.newLink = function() {
		this.link_item_forms.show();
		this.link_item_new_button.hide();
		$('select[name="cflk-types"]', this.link_item_forms).focus();
	};

	// cancel new link form
	cflk.cancelNewLink = function() {
		this.link_item_forms.hide();
		this.link_item_new_button.show();
	};
	
	// send data to server to get display state of link item
	cflk.processLink = function(link_id) {
		var form_data = $('.cflk-type-forms li:visible :input', this.link_item_forms).serialize();		
		var data = {
			action:'cflk_ajax',
			func:'get_link_view',
			args:JSON.stringify({form_data:form_data, id:link_id})
		};
		
		$.post(
			this.opts.ajax_url,
			data,
			function(r, statusText) {
				if (r.success) {
					cflk.insertLink(r);
				}
				else {
					cflk.error(r.message);
				}
			},
			'json'
		);
	};
	
	// insert new or edited link HTML in to the list
	cflk.insertLink = function(data) {
		if (this.no_items_list_item.is(':visible')) {
			this.no_items_list_item.hide();
		}
		
		this.link_item_forms.hide();
		this.link_item_new_button.show();

		if (data.new_link) {
			this.links_list.append($('<li id="' + data.id + '"/>').html(data.html));
		}
		else {
			$('#' + data.id).html(data.html);
		}
		
		this.new_link_form.resetForm();
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
		$('#cflk-type-display-' + _this.val(), _this.parents('div.cflk-type-select').siblings('.cflk-type-type'))
			.css({'display':'block'})
			.siblings()
			.css({'display':'none'});
	};

// Edit Links

	cflk.editLink = function(clicked) {
		var _parent = $(clicked).parents('li');
		var _data = _parent.find('.clfk-link-data').val();
		
		var data = {
			action:'cflk_ajax',
			func:'get_link_edit_form',
			args:JSON.stringify({form_data:JSON.parse(_data), link_id:_parent.attr('id')})
		};
	
		$.post(
			this.opts.ajax_url,
			data,
			function(r, statusText) {
				if (r.success) {
					cflk.insertEditForm(r);
				}
				else {
					cflk.error(r.message);
				}
			},
			'json'
		);
	};
	
	cflk.insertEditForm = function(data) {
		_target = $('#' + data.id, this.links_list);

		if (_target.size() > 0) {
			$('.cflk-link-data-display', _target).hide().after(data.html);
		}
		else {
			cflk.error('Invalid link ID in return data');
		}
	};
	
	cflk.cancelEditLink = function(clicked) {
		var _parent = $(clicked).parents('li');
		$('div.cflk-edit-link-form', _parent).remove();
		$('.cflk-link-data-display', _parent).show();
	};
	
	cflk.processEditLink = function(clicked) {
		var _parent = $(clicked).parents('li');
		var _form_data = $('.cflk-edit-link-form :input', _parent).serialize();
		var _link_id = _parent.attr('id');
		
		var data = {
			action:'cflk_ajax',
			func:'get_link_view',
			args:JSON.stringify({form_data:_form_data, id:_link_id})
		};
	
		$.post(
			this.opts.ajax_url,
			data,
			function(r, statusText) {
				if (r.success) {
					cflk.insertLink(r);
				}
				else {
					cflk.error(r.message);
				}
			},
			'json'
		);		
	};
	
	cflk.confirmDeleteLink = function(clicked) {
		if (confirm('Are you sure you want to delete this link? It will not be completely deleted until you save changes on the list.')) {
			$(clicked).parents('li').remove();
			if ($('.cflk-item', this.links_list).size() < 1) {
				this.no_items_list_item.show();
			}
		}
	};
	
	cflk.submitListEditForm = function() {
		$("#cflk-list-submit-button").trigger('click');
	};
	
	cflk.toggleListDetailsEdit = function(dir) {
		switch(dir) {
			case 'open':
				this.list_details.hide();
				this.list_details_edit.show();
				break;
			case 'close':
				this.list_details.show();
				this.list_details_edit.hide();
				break;
		}
	};
	

// Error Handling

	cflk.error = function(message) {
		alert(message);
	};
	
	$(cflk).bind('ajaxError', function(evt, xhr, opts, err) {
		cflk.error('There was an error contacting the server');
	});
	
// Init

	$(function(){
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
	
		// Main Page Actions
		if ($('#cflk-available-lists').size() == 1) {
			
			$('.cflk-list-edit').click(function() {
				
				return false;
			});
			
			$('.cflk-list-delete').click(function() {
			
				return false;
			});
		}
	
		// List Edit Page Actions
		if ($('#cflk-list-form').size() == 1) {
			
			cflk.editInit();
			
			// New Link Actions
			$('#cflk-new-list-item').click(function() {
				cflk.newLink();
				return false;
			});
			$(document).bind('keydown','ctrl+n', function() {
				cflk.newLink();
				return false;
			});
	
			$('#cflk-list-items-footer .cflk-link-edit-done').click(function() {
				cflk.processLink();
				return false;
			});
	
			$('#cflk-list-items-footer .cflk-cancel').click(function() {
				cflk.cancelNewLink();
				return false;
			});
		
			// Edit Link Actions
			$('.cflk-edit-link').live('click', function() {
				cflk.editLink(this);
				return false;
			});
		
			$('.cflk-delete-link').live('click', function() {
				cflk.confirmDeleteLink(this);
				return false;
			});
		
			$('.cflk-edit-done').live('click', function() {
				cflk.processEditLink(this);
				return false;
			});
		
			$('.cflk-edit-cancel').live('click', function() {
				cflk.cancelEditLink(this);
				return false;
			});
		
			// give focus to the list name if its visible
			$('#cflk_list_name:visible').focus();

			// Item type select
			$('select[name="cflk-types"]').change(cflk.selectType);

			// Unique list slug setting
			$('.cflk-list-new #cflk_list_name').blur(function() {
				cflk.getNewListId();
			});
		
			// Page Submit
			$('#cflk-list-submit').click(function(){
				cflk.submitListEditForm();
			});
			$(document).bind('keydown', 'ctrl+s', function() {
				cflk.submitListEditForm();
				return false;
			});

			// Edit List Details
			$('#cflk-edit-list-details').click(function() {
				cflk.toggleListDetailsEdit('open');
				return false;
			});
			
			$('#cflk-edit-list-details-cancel a').click(function() {
				cflk.toggleListDetailsEdit('close');
				return false;
			});
		}
	});
	
})(jQuery);