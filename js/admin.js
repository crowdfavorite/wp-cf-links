(function($) {

// Links manager object
	window.cflk = {};		
		
	cflk.opts = {
		ajax_url:ajaxurl, // ajaxurl is predefined in the admin by WordPress
		views:{} // empty placeholder for item view states for inserting new items, filled by ajax calls
	};
		
	cflk.editInit = function() {
		this.links_list = $('#menu-to-edit');
		this.new_link_form = $('#menu-item-new');
		this.link_item_forms = $('#new-item-edit');
		this.link_item_new_button = $('#menu-item-new .menu-item-bar');
		this.no_items_list_item = $('#cflk-list-sortable .cflk-no-items');
		this.list_details = $('#cflk-edit-list-details-display');
		this.list_details_edit = $('#cflk-edit-list-details-edit');
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

// Delete List
	
	cflk.deleteList = function(list_key) {
		if (confirm('Are you sure you want to delete this list? There is no way to undo this delete.')) {
			$("#cflk-list-"+list_key).remove();
			var data = {
				action:'cflk_ajax',
				func:'delete_list',
				args:JSON.stringify({list_key:list_key})
			};

			$.post(
				this.opts.ajax_url,
				data,
				function(r, statusText) {
					if (r.success) {
						$(".cflk-navigation").prepend(r.html_message);
					}
					else {
						cflk.error(r.message);
					}
				},
				'json'
			);
		}
		return false;
	}

// Export List

	cflk.exportList = function(list_key) {
		var data = {
			action:'cflk_ajax',
			func:'export_list',
			args:JSON.stringify({list_key:list_key})
		};

		$.post(
			this.opts.ajax_url,
			data,
			function(r, statusText) {
				cflk.popup(r.message)
			},
			'json'
		);
	}

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
		var form_data = $(':input:visible', this.link_item_forms).serialize();		
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
			wpNavMenu.addMenuItemToBottom($('<li id="' + data.id + '" class="cflk-item menu-item menu-item-depth-0 menu-item-edit-inactive" />').html(data.html));
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
		$(".cflk-edit-forms li").hide();
		$("#cflk-type-"+_this.val()).show();
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
			$('.menu-item-bar', _target).hide().after(data.html);
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

// Popup Handling
	
	cflk.popup = function(html) {
		var t_html = "<div id=\"disposible-wapper\">"+html+"</div>";
		var w = 500;
		var h = 500;
		
		var opts = {
			windowSourceID:t_html,
			borderSize:0,
			windowBGColor:"transparent",
			windowPadding: 0,
			positionType:"centered",
			width:w,
			height:h,
			overlay:1,
			overlayOpacity:"65"
		};
		$.openDOMWindow(opts);
		$('#DOMWindow').css('overflow','visible');
		
		// fix the height on browsers that don't honor the max-height css directive
		var _contentdiv = $('#DOMWindow .cflk-popup-content');
		if (_contentdiv.height() > h-20) {
			_contentdiv.css({'height':(h-20) + 'px'});
		} 
		
		$(".cflk-popup-close").click(function(){
			$.closeDOMWindow();
			return false;
		});
		
		return true;
	}
	
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
		$(".cflk-delete-list").click(function() {
			var list_key = $(this).attr('id').replace('list-delete-', '');
			cflk.deleteList(list_key);
			return false;
		});
		
		$(".cflk-export-list").click(function() {
			var list_key = $(this).attr('id').replace('list-export-', '');
			cflk.exportList(list_key);
			return false;
		});
	
		// List Edit Page Actions
		if ($('#cflk-list-form').size() == 1) {
			
			cflk.editInit();
			
			// New Link Actions
			$('#cflk-new-list-item').live('click', function() {
				cflk.newLink();
				return false;
			});
	
			// New Edit Item Actions
			
			$('#menu-item-new-button').live('click', function() {
				cflk.newLink();
				return false;
			});
			
			$("#new-item-edit a.new-edit-done").live('click', function() {
				cflk.processLink();
				$("#new-title").val('');
				$("#new-custom-class").val('');
				$("#new-opennew").attr('checked', '');
				return false;
			});
			
			$("#new-item-edit a.new-edit-remove").live('click', function() {
				cflk.cancelNewLink();
				$("#new-title").val('');
				$("#new-custom-class").val('');
				$("#new-opennew").attr('checked', '');
				return false;
			});
			
			$('.item-actions a').live('click', function() {
				cflk.editLink(this);
				return false;
			});
		
			$('.edit-done').live('click', function() {
				cflk.processEditLink(this);
				return false;
			});
			
			$('.edit-remove').live('click', function() {
				cflk.confirmDeleteLink(this);
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
			$('#new-type-selector').change(cflk.selectType);

			// Unique list slug setting
			$('.cflk-list-new #cflk_list_name').blur(function() {
				cflk.getNewListId();
			});
		
			// Page Submit
			$('#cflk-list-submit').click(function(){
				cflk.submitListEditForm();
			});

			// Edit List Details
			$('#cflk-edit-list-details-button').click(function() {
				cflk.toggleListDetailsEdit('open');
				return false;
			});
			
			$('#cflk-edit-list-details-cancel a').click(function() {
				cflk.toggleListDetailsEdit('close');
				return false;
			});
			
			// Export
			$("#cflk-list-export").click(function() {
				var list_key = $("#cflk_list_key").val();
				cflk.exportList(list_key);
				return false;
			});
		}
	});
	
})(jQuery);