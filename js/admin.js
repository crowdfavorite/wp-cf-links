// When the document is ready set up our sortable with its inherant function(s)
	jQuery(document).ready(function() {
		jQuery("#cflk-list").sortable({
			handle : ".handle",
			update : function () {
				jQuery("input#cflk-log").val(jQuery("#cflk-list").sortable("serialize"));
			},
			opacity: 0.5,
			stop: cflk_levels_refactor
		});
		jQuery('input[name="link_edit"]').click(function() {
			location.href = "options-general.php?page=cf-links.php&cflk_page=edit&link=" + jQuery(this).attr('rel');
			return false;
		});
		jQuery('tr.tr_holder').each(function() {
			jQuery('#message_import_problem').attr('style','');
		});
	});
	function deleteLink(cflk_key,linkID) {
		if (confirm('Are you sure you want to delete this?')) {
			if (cflkAJAXDeleteLink(cflk_key,linkID)) {
				jQuery('#listitem_'+linkID).remove();
				jQuery("#message_delete").attr("style","");
				cflk_levels_refactor();
			}
			return false;
		}
	}
	function deleteCreated(linkID) {
		if (confirm('Are you sure you want to delete this?')) {
			jQuery('#listitem_'+linkID).remove();
			return false;
		}
	}
	function deleteMain(cflk_key) {
		if (confirm('Are you sure you want to delete this?')) {
			if (cflkAJAXDeleteMain(cflk_key)) {
				jQuery('#link_main_'+cflk_key).remove();
				jQuery("#message_delete").attr("style","");
			}
			return false;
		}
	}
	function editNicename() {
		jQuery('#cflk_nicename_h3').attr('style','display: none;');
		jQuery('#cflk_nicename_input').attr('style','');
	}
	function cancelNicename() {
		jQuery('#cflk_nicename_input').attr('style','display: none;');
		jQuery('#cflk_nicename_h3').attr('style','');
	}
	function saveNicename(cflk_key) {
		if (cflkAJAXSaveNicename(cflk_key, jQuery("#cflk_nicename_new").val())) {
			jQuery("#message").attr("style","");
			jQuery("#cflk_nicename_text").text(jQuery("#cflk_nicename_new").val());
			jQuery("#cflk_nicename").text(jQuery("#cflk_nicename_new").val());
			jQuery("#cflk_nicename_h2").text(jQuery("#cflk_nicename_new").val());
			cancelNicename();
		}
	}
	function editTitle(key) {
		jQuery('#cflk_'+key+'_title_edit').attr('style','display: none;');
		jQuery('#cflk_'+key+'_title_input').attr('style','');
	}
	function clearTitle(key) {
		jQuery('#cflk_'+key+'_title_input').attr('style','display: none;');
		jQuery('#cflk_'+key+'_title_edit').attr('style','');
		jQuery('#cflk_'+key+'_title').val('');
	}
	function editDescription() {
		jQuery('#description_text').attr('style', 'display:none;');
		jQuery('#description_edit').attr('style', '');
		jQuery('#description_edit_btn').attr('style', 'display:none;');
		jQuery('#description_cancel_btn').attr('style', '');
	}
	function cancelDescription() {
		jQuery('#description_text').attr('style', '');
		jQuery('#description_edit').attr('style', 'display:none;');
		jQuery('#description_edit_btn').attr('style', '');
		jQuery('#description_cancel_btn').attr('style', 'display:none;');
	}
	function showLinkType(key) {
		var type = jQuery('#cflk_'+key+'_type option:selected').val();
		jQuery('#'+type+'_'+key).attr('style','').siblings().attr('style','display: none;');
	}
	function showLinkCode(key) {
		jQuery('#'+key).slideToggle();
	}
	function addLink() {
		var id = new Date().valueOf();
		var section = id.toString();
		
		var html = jQuery('#newitem_SECTION').html().replace(/###SECTION###/g, section);
		jQuery('#cflk-list').append(html);
		jQuery('#listitem_'+section).attr('style','').find('td.link-value span:first-child').attr('style','');

		// activates level indent buttons
		cflk_set_level_buttons('listitem_'+section); 
		cflk_levels_refactor();
	}
	function changeExportList() {
		var list = jQuery('#list-export').val();
		var btn = jQuery('#cflk-export-btn');
		btn.attr('alt', 'index.php?cflk_page=export&height=400&width=600&link='+list);	
	}
	
// Link Level Functionality

	// initialize the list for multiple levels
	jQuery(function(){
		// prep
		cflk_set_level_buttons();
		cflk_levels_refactor();
	});

	// initialize the level buttons
	function cflk_set_level_buttons(parent_id) {
		// add actions to the rest of the list-level modifiers
		if(parent_id == undefined) {
			parent_id = 'cflk-list';
		}
		jQuery('#' + parent_id + ' button.level-decrement, #' + parent_id + ' button.level-increment').click(function() {
			clicked = jQuery(this);
			target = clicked.parents('div').children('input');
			item_id = clicked.parents('li').attr('id').replace('listitem_','');
		
			if (clicked.hasClass('level-increment') && cflk_can_increment_level(target)) {
				cflk_update_link_level(target,+1);
			}
			else if (clicked.hasClass('level-decrement') && cflk_can_decrement_level(target)) {
				cflk_update_link_level(target,-1);
			}
			cflk_levels_refactor();
			return false;
		});
	}
	
	// toggle the buttons visible state for wether it can be used or not
	function cflk_toggle_button_usability(current,blank_button) {
		jQuery(current).find('td.link-level button').each(function(i){
			_this = jQuery(this);
			if(blank_button) {
				_this.css('opacity',0).addClass('disabled');
			}
			else if(_this.hasClass('level-decrement') && !cflk_can_decrement_level(_this.parents('div').children('input'))) {
				_this.css('opacity',0.25).addClass('disabled');
			}
			else if(_this.hasClass('level-increment') && !cflk_can_increment_level(_this.parents('div').children('input'))) {
				_this.css('opacity',0.25).addClass('disabled');
			}	
			else {
				_this.css('opacity',1).removeClass('disabled');
			}	
		});
	}

	// move the link
	function cflk_update_link_level(obj,amount) {
		obj.val(parseInt(obj.val())+amount);
		obj.parents('li').attr('class','level-'+obj.val());
	}

	// figure out if the item is allowed to go indent
	function cflk_can_increment_level(target) {
		// make sure we are no more than 1 more than the previous sibling						
		prev_value = parseInt(target.parents('li').prev().find('td.link-level input.link-level-input').val());
		if(parseInt(target.val())+1 > prev_value+1) { return false; }
		return true;
	}

	// figure out if the item is allowed to outdent
	function cflk_can_decrement_level(target) {
		if(target.val() == 0) { return false; }
		return true;
	}

	// refactor the list levels so that nobody is more than 1 level deeper than its parent
	function cflk_levels_refactor() {
		jQuery('#cflk-list li').each(function(i){
			current = jQuery(this);
			var current_val = parseInt(current.find('td.link-level input.link-level-input').val());
			// handle first row
			if(i == 0) {
				if(current_val > 0) {
					cflk_update_link_level(current.find('td.link-level input.link-level-input'),'-' + parseInt(current_val)+1);
					current.find('td.link-level input.link-level-input').val(0);
				}
				cflk_toggle_button_usability(current,true);
				prev = current;
				return; 
			}
			
			// handle not first rows
			var prev_val = parseInt(prev.find('td.link-level input.link-level-input').val());
			if(current_val > prev_val+1) {
				diff = current_val - (prev_val+1);
				cflk_update_link_level(current.find('td.link-level input.link-level-input'),parseInt('-'+diff));
			}
			
			cflk_toggle_button_usability(current);
						
			prev = current;
		});
	}
	
	// provide modal edit abilities
	function cflk_edit_select_modal(id,value,listname) {
		var container = jQuery('#' + listname + '_' + id + ' .select-modal-display');
		var hidden = jQuery('#cflk-' + listname + '-' + id + '-value');
		var currentvalue = hidden.val();
		var value_id = hidden.attr('id');
		var value_name = hidden.attr('name');
				
		container.hide();
		hidden.remove();
		
		var select_container = jQuery('#' + listname + '-modal').clone();
		select_container.find('select').attr('name',value_name).attr('id',value_id);
		select_container.find('option').each(function(){
			this.selected = this.value == currentvalue;
		});
		
		select_container.css({'display':'inline'}).insertAfter(container)
			.children('span#' + listname + '_list').css({'display':'inline'})
			.siblings('input.modal-done').click(function(){
				_this = jQuery(this);
				var new_value = jQuery('#cflk-' + listname + '-' + id + '-value').val();
				var new_hidden = jQuery('<input type="hidden">')
					.attr('name',value_name)
					.attr('id',value_id)
					.val(new_value);
				container.append(new_hidden);
				
				jQuery('#' + listname + '_' + id + ' .select-modal-display').find('span').html(_this.siblings('span').children('select').find('option:selected').text());				
				select_container.remove();
				select_container = null; // possibly feeble attempt at keeping memory use low
				container.show();
			});
	}