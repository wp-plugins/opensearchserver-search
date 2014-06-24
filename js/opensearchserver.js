if (typeof (OpenSearchServer) == "undefined")
	OpenSearchServer = {};

OpenSearchServer.getXmlHttpRequestObject = function() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		return new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		return null;
	}
};

OpenSearchServer.xmlHttp = OpenSearchServer.getXmlHttpRequestObject();

OpenSearchServer.setAutocomplete = function(value) {
	var parent = OpenSearchServer.usedInput.parents('form');
	//if autocomplete div does not exist creates it
	if(parent.children('.oss-autocomplete').length == 0) { 
		parent.append("<div class='oss-autocomplete oss-autocomplete-from-class-"+OpenSearchServer.usedInput.attr('class')+"  oss-autocomplete-from-id-"+OpenSearchServer.usedInput.attr('id')+"'></div>");
	}
	//get autocomplete div and replace its html
	var ac = parent.children('.oss-autocomplete');
	ac.html(value);
	return ac;
};

OpenSearchServer.selectedAutocomplete = 0;
OpenSearchServer.autocompleteSize = 0;
OpenSearchServer.usedInput = null;

OpenSearchServer.getselectedautocompletediv = function(n) {
	return OpenSearchServer.usedInput.parents('form').find('#oss-autocompleteitem' + n);
};

OpenSearchServer.autosuggest = function(event, usedInput) {
	OpenSearchServer.usedInput = usedInput;
	if (OpenSearchServer.xmlHttp.readyState != 4
			&& OpenSearchServer.xmlHttp.readyState != 0)
		return;
	var str = escape(usedInput.val());
	if (str.length == 0) {
		OpenSearchServer.setAutocomplete('');
		return;
	}

	OpenSearchServer.xmlHttp.open("GET", '?s=ossautointernal&q=' + str, true);
	OpenSearchServer.xmlHttp.onreadystatechange = OpenSearchServer.handleAutocomplete;
	OpenSearchServer.xmlHttp.send(null);
	return true;
};

OpenSearchServer.navigation = function(event, usedInput) {
	OpenSearchServer.usedInput = usedInput;
	var keynum = 0;
	if (window.event) { // IE
		keynum = event.keyCode;
	} else if (event.which) { // Netscape/Firefox/Opera
		keynum = event.which;
	}
	if (keynum == 38 || keynum == 40) {
		if (selectedAutocomplete > 0) {
			OpenSearchServer.getselectedautocompletediv(selectedAutocomplete).siblings().removeClass('oss-autocomplete_link_over');
		}
		if (keynum == 38) {
			if (selectedAutocomplete > 0) {
				selectedAutocomplete--;
			}
		} else if (keynum == 40) {
			if (selectedAutocomplete < autocompleteSize) {
				selectedAutocomplete++;
			}
		}
		if (selectedAutocomplete > 0) {
			OpenSearchServer.autocompleteLinkOver(selectedAutocomplete);
			OpenSearchServer.setKeywords(OpenSearchServer.getselectedautocompletediv(selectedAutocomplete).html());
		}
		return false;
	}
};


OpenSearchServer.handleAutocomplete = function() {
	if (OpenSearchServer.xmlHttp.readyState != 4)
		return;
	var ac = OpenSearchServer.setAutocomplete('');
	var resp = OpenSearchServer.xmlHttp.responseText;
	if (resp == null) {
		return;
	}
	if (resp.length == 0) {
		return;
	}
	var str = resp.split("\n");
	var content = '<div class="oss-autocompletelist">';
	for ( var i = 0; i < str.length - 1; i++) {
		var j = i + 1;
		content += '<div id="oss-autocompleteitem' + j + '" ';
		/* 
		content += 'onmouseover="javascript:OpenSearchServer.autocompleteLinkOver('
				+ j + ');" ';
		content += 'onmouseout="javascript:OpenSearchServer.autocompleteLinkOut('
				+ j + ');" ';
		content += 'onclick="javascript:OpenSearchServer.setKeywords_onClick(this.innerHTML);" ';
		*/
		content += 'class="oss-autocomplete_link">' + str[i].trim() + '</div>';
	}
	content += '</div>';
	ac.html(content);
	selectedAutocomplete = 0;
	autocompleteSize = str.length;
};

OpenSearchServer.autocompleteLinkOver = function(n) {
	var elt = OpenSearchServer.getselectedautocompletediv(n);
	//remove "over" class from every siblings
	elt.siblings().removeClass('oss-autocomplete_link_over');
	//add "over" class
	elt.addClass('oss-autocomplete_link_over');
	selectedAutocomplete = n;
};

OpenSearchServer.autocompleteLinkOut = function(n) {
	var elt = OpenSearchServer.getselectedautocompletediv(n);
	//remove "over" class from every siblings
	elt.siblings().removeClass('oss-autocomplete_link_over');
};

OpenSearchServer.setKeywords = function(value) {
	OpenSearchServer.usedInput.val(value).focus();
};

jQuery(function($) {
	//facet with radio button, click on radio button must simulate click on link
	$('.oss-nav-radio input[type=radio]').click(function(e) {
		window.location.href = $(this).siblings('label').children('a').attr('href');
	})
	
	// autocomplete on search page
	$('input#oss-keyword').on('input', function(event) {
    	OpenSearchServer.autosuggest(event, $(this));
    });
	$('input#oss-keyword').on('keyup', function(event) {
    	OpenSearchServer.navigation(event, $(this));
    });
	
	// autocomplete on main Wordpress search input
	$('input.search-field').attr('autocomplete', 'off');
	$('input.search-field').on('input', function(event) {
    	OpenSearchServer.autosuggest(event, $(this));
    });
	$('input.search-field').on('keyup', function(event) {
    	OpenSearchServer.navigation(event, $(this));
    });

	//autocomplete over, out, click
	$('body').on('mouseover', '.oss-autocomplete_link', function() {
		//remove "over" class from every siblings
		$(this).siblings().removeClass('oss-autocomplete_link_over');
		$(this).addClass('oss-autocomplete_link_over');
	});
	$('body').on('mouseout', '.oss-autocomplete_link', function() {
		$(this).removeClass('oss-autocomplete_link_over');
	});
	$('body').on('click', '.oss-autocomplete_link', function() {
		OpenSearchServer.usedInput.val($(this).html().trim()).focus();
		OpenSearchServer.setAutocomplete('');
		OpenSearchServer.usedInput.parents('form').submit();
	});
});



/*
OpenSearchServer.autocompleteLinkOver = function(n) {
	if (selectedAutocomplete > 0) {
		OpenSearchServer.autocompleteLinkOut(selectedAutocomplete);
	}
	var dv = OpenSearchServer.getselectedautocompletediv(n);
	if (dv != null) {
		dv.className = 'oss-autocomplete_link_over';
		selectedAutocomplete = n;
	}
};

OpenSearchServer.autocompleteLinkOut = function(n) {
	var dv = OpenSearchServer.getselectedautocompletediv(n);
	if (dv != null) {
		dv.className = 'oss-autocomplete_link';
	}
};

OpenSearchServer.setKeywords_onClick = function(value) {
	//var dv = document.getElementById('oss-keyword');
	var dv = OpenSearchServer.usedInput;
	if (dv != null) {
		dv.val(value);
		dv.focus();
		OpenSearchServer.setAutocomplete('');
		//document.forms['oss-searchform'].submit();
		OpenSearchServer.usedInput.parents('form').submit();
		return true;
	}
};
*/