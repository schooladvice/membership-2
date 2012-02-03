function m_removeaction() {
	var section = jQuery(this).attr('id');
	var sectionname = section.replace('remove-','');

	jQuery('#main-' + sectionname).appendTo('#hiden-actions');
	jQuery('#' + sectionname).show();

	// Move from the fields
	jQuery('#in-positive-rules').val( jQuery('#in-positive-rules').val().replace(',' + sectionname, ''));
	jQuery('#in-negative-rules').val( jQuery('#in-negative-rules').val().replace(',' + sectionname, ''));

	return false;
}

function m_addnewlevel() {
	window.location = "?page=membershiplevels&action=edit&level_id=";

	return false;
}

function m_deactivatelevel() {
	if(confirm(membership.deactivatelevel)) {
		return true;
	} else {
		return false;
	}
}

function m_deletelevel() {
	if(confirm(membership.deletelevel)) {
		return true;
	} else {
		return false;
	}
}

function m_clickactiontoggle() {
	if(jQuery(this).parent().hasClass('open')) {
		jQuery(this).parent().removeClass('open').addClass('closed');
		jQuery(this).parents('.action').find('.action-body').removeClass('open').addClass('closed');
	} else {
		jQuery(this).parent().removeClass('closed').addClass('open');
		jQuery(this).parents('.action').find('.action-body').removeClass('closed').addClass('open');
	}
}

function m_addtopositive() {

	moving = jQuery(this).parents('.draggable-level').attr('id');
	ruleplace = 'positive-rules';
	if(moving != '') {
		jQuery('#main-' + moving).prependTo('#' + ruleplace + '-holder');
		jQuery('#' + moving).hide();

		// put the name in the relevant holding input field
		jQuery('#in-' + ruleplace).val( jQuery('#in-' + ruleplace).val() + ',' + moving );

	}

	return false;
}

function m_addtonegative() {

	moving = jQuery(this).parents('.draggable-level').attr('id');
	ruleplace = 'negative-rules';
	if(moving != '') {
		jQuery('#main-' + moving).prependTo('#' + ruleplace + '-holder');
		jQuery('#' + moving).hide();

		// put the name in the relevant holding input field
		jQuery('#in-' + ruleplace).val( jQuery('#in-' + ruleplace).val() + ',' + moving );

	}

	return false;
}

function m_clickpositivetab() {

	if(jQuery(this).parents('.positivetab').hasClass('activetab')) {
		return false;
	} else {
		jQuery('.activetab').removeClass('activetab');
		jQuery('.positivetab').addClass('activetab');

		jQuery('.positivecontent').removeClass('inactivecontent').addClass('activecontent');
		jQuery('.negativecontent').removeClass('activecontent').addClass('inactivecontent');
		jQuery('.advancedcontent').removeClass('activecontent').addClass('inactivecontent');

		jQuery('.level-holder h3').css('display', 'none');
	}

	return false;
}

function m_clicknegativetab() {

	if(jQuery(this).parents('.negativetab').hasClass('activetab')) {
		return false;
	} else {
		jQuery('.activetab').removeClass('activetab');
		jQuery('.negativetab').addClass('activetab');

		jQuery('.negativecontent').removeClass('inactivecontent').addClass('activecontent');
		jQuery('.positivecontent').removeClass('activecontent').addClass('inactivecontent');
		jQuery('.advancedcontent').removeClass('activecontent').addClass('inactivecontent');

		jQuery('.level-holder h3').css('display', 'none');
	}

	return false;
}

function m_clickadvancedtab() {

	if(jQuery(this).parents('.advancedtab').hasClass('activetab')) {
		return false;
	} else {
		jQuery('.activetab').removeClass('activetab');
		jQuery('.advancedtab').addClass('activetab');

		jQuery('.positivecontent').removeClass('inactivecontent').addClass('activecontent');
		jQuery('.negativecontent').removeClass('inactivecontent').addClass('activecontent');
		jQuery('.advancedcontent').removeClass('inactivecontent').addClass('activecontent');

		jQuery('.level-holder h3').css('display', 'block');
	}

	return false;
}

function m_levelsReady() {


	jQuery('.draggable-level').draggable({
			opacity: 0.7,
			helper: 'clone',
			start: function(event, ui) {
					jQuery('input#beingdragged').val( jQuery(this).attr('id') );
				 },
			stop: function(event, ui) {
					jQuery('input#beingdragged').val( '' );
				}
				});

	jQuery('.level-droppable-rules').droppable({
			hoverClass: 'hoveringover',
			drop: function(event, ui) {
					moving = jQuery('input#beingdragged').val();
					ruleplace = jQuery(this).attr('id');
					if(moving != '') {
						jQuery('#main-' + moving).prependTo('#' + ruleplace + '-holder');
						jQuery('#' + moving).hide();

						// put the name in the relevant holding input field
						jQuery('#in-' + ruleplace).val( jQuery('#in-' + ruleplace).val() + ',' + moving );

					}
				}
	});

	jQuery('#positive-rules-holder').sortable({
		opacity: 0.7,
		helper: 'clone',
		placeholder: 'placeholder-rules',
		update: function(event, ui) {
				jQuery('#in-positive-rules').val(',' + jQuery('#positive-rules-holder').sortable('toArray').join(',').replace(/main-/gi, ''));
			}
	});

	jQuery('#negative-rules-holder').sortable({
		opacity: 0.7,
		helper: 'clone',
		placeholder: 'placeholder-rules',
		update: function(event, ui) {
				jQuery('#in-negative-rules').val(',' + jQuery('#negative-rules-holder').sortable('toArray').join(',').replace(/main-/gi, ''));
			}
	});

	jQuery('a.removelink').click(m_removeaction);
	jQuery('.addnewlevelbutton').click(m_addnewlevel);

	jQuery('.deactivate a').click(m_deactivatelevel);
	jQuery('.delete a').click(m_deletelevel);

	jQuery('.action .action-top .action-button').click(m_clickactiontoggle);

	jQuery('a.action-to-positive').click(m_addtopositive);
	jQuery('a.action-to-negative').click(m_addtonegative);

	jQuery('.positivetab a').click(m_clickpositivetab);
	jQuery('.negativetab a').click(m_clicknegativetab);
	jQuery('.advancedtab a').click(m_clickadvancedtab);

}

jQuery(document).ready(m_levelsReady);