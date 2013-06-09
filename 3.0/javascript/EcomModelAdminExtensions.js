
;(function($) {
	$(document).ready(
		function() {
			EcomModelAdminExtensions.init();
		}
	);
})(jQuery);

var EcomModelAdminExtensions = {

	delegateRootSelector: '#right',

	saveButtonSelector: '#action_doSave',

	currButtonSelector: '#action_goCurr',

	nextButtonSelector: '#action_goNext',

	prevButtonSelector: '#action_goPrev',

	currRecordURLSelector: '#currRecordURL',

	nextRecordURLSelector: '#nextRecordURL',

	prevRecordURLSelector: '#prevRecordURL',

	forwardButtonSelector: '#Form_EditForm_action_goForward, #Form_ResultsForm_action_goForward',

	backwardButtonSelector: '#Form_EditForm_action_goBack, #Form_ResultsForm_action_goBack',

	rightSideModelAdminPanelSelector: '#right #ModelAdminPanel',

	//setup next and previous buttons
	init: function () {
		jQuery(EcomModelAdminExtensions.delegateRootSelector).delegate(
			EcomModelAdminExtensions.nextButtonSelector,
			'click',
			function() {
				nextPage = jQuery(EcomModelAdminExtensions.nextRecordURLSelector).val();
				EcomModelAdminExtensions.loadForm(nextPage);
				return false;
			}
		);
		jQuery(EcomModelAdminExtensions.delegateRootSelector).delegate(
			EcomModelAdminExtensions.prevButtonSelector,
			'click',
			function() {
				prevPage = jQuery(EcomModelAdminExtensions.prevRecordURLSelector).val();
				EcomModelAdminExtensions.loadForm(prevPage);
				return false;
			}
		);
		jQuery(EcomModelAdminExtensions.delegateRootSelector).delegate(
			EcomModelAdminExtensions.currButtonSelector,
			'click',
			function() {
				currPage = jQuery(EcomModelAdminExtensions.currRecordURLSelector).val();
				EcomModelAdminExtensions.loadForm(currPage);
				return false;
			}
		);
	},

	//load form (called from previous and next buttons....)
	loadForm: function(url) {
		tinymce_removeAll();
		jQuery(EcomModelAdminExtensions.rightSideModelAdminPanelSelector).load(
			url,
			function(result) {
				if(typeof(successCallback) == 'function') {
					successCallback.apply();
				}
				jQuery(EcomModelAdminExtensions.forwardButtonSelector).hide();
				jQuery(EcomModelAdminExtensions.backwardButtonSelector).hide();

				Behaviour.apply(); // refreshes ComplexTableField
				if(window.onresize) {
					window.onresize();
				}
			}
		)
	}
}


