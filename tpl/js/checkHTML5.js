(function($){
	function checkInput(type) {
		var input = document.createElement('input');
		input.setAttribute('type', type);
		return input.type == type;
	}

	window.supportedHTML5 = {};
	window.supportedHTML5.input = {};
	window.supportedHTML5.input.date = checkInput('date');
})(jQuery);