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
	var ac = document.getElementById('oss-autocomplete');
	ac.innerHTML = value;
	return ac;
};

OpenSearchServer.selectedAutocomplete = 0;
OpenSearchServer.autocompleteSize = 0;

OpenSearchServer.getselectedautocompletediv = function(n) {
	return document.getElementById('oss-autocompleteitem' + n);
};

OpenSearchServer.autosuggest = function(event) {
	var keynum = 0;
	if (window.event) { // IE
		keynum = event.keyCode;
	} else if (event.which) { // Netscape/Firefox/Opera
		keynum = event.which;
	}
	if (keynum == 38 || keynum == 40) {
		if (selectedAutocomplete > 0) {
			OpenSearchServer.autocompleteLinkOut(selectedAutocomplete);
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
			var dv = OpenSearchServer.getselectedautocompletediv(selectedAutocomplete);
			OpenSearchServer.autocompleteLinkOver(selectedAutocomplete);
			OpenSearchServer.setKeywords(dv.innerHTML);
		}
		return false;
	}

	if (OpenSearchServer.xmlHttp.readyState != 4
			&& OpenSearchServer.xmlHttp.readyState != 0)
		return;
	var str = escape(document.getElementById('oss-keyword').value);
	if (str.length == 0) {
		OpenSearchServer.setAutocomplete('');
		return;
	}

	OpenSearchServer.xmlHttp.open("GET", '?s=ossautointernal&q=' + str, true);
	OpenSearchServer.xmlHttp.onreadystatechange = OpenSearchServer.handleAutocomplete;
	OpenSearchServer.xmlHttp.send(null);
	return true;
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
	var content = '<div id="oss-autocompletelist">';
	for ( var i = 0; i < str.length - 1; i++) {
		var j = i + 1;
		content += '<div id="oss-autocompleteitem' + j + '" ';
		content += 'onmouseover="javascript:OpenSearchServer.autocompleteLinkOver('
				+ j + ');" ';
		content += 'onmouseout="javascript:OpenSearchServer.autocompleteLinkOut('
				+ j + ');" ';
		content += 'onclick="javascript:OpenSearchServer.setKeywords_onClick(this.innerHTML);" ';
		content += 'class="oss-autocomplete_link">' + str[i] + '</div>';
	}
	content += '</div>';
	ac.innerHTML = content;
	selectedAutocomplete = 0;
	autocompleteSize = str.length;
};

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
	var dv = document.getElementById('oss-keyword');
	if (dv != null) {
		dv.value = value;
		dv.focus();
		OpenSearchServer.setAutocomplete('');
		document.forms['oss-searchform'].submit();
		return true;
	}
};

OpenSearchServer.setKeywords = function(value) {
	var dv = document.getElementById('oss-keyword');
	if (dv != null) {
		dv.value = value;
		dv.focus();
	}
};