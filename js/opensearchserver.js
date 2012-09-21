function getXmlHttpRequestObject() {
	if (window.XMLHttpRequest) {
		return new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		return new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		return null;
	}
}

var xmlHttp = getXmlHttpRequestObject();

function setAutocomplete(value) {
	var ac = document.getElementById('autocomplete');
	ac.innerHTML = value;
	return ac;
}

var selectedAutocomplete = 0;
var autocompleteSize = 0;

function getselectedautocompletediv(n) {
	return document.getElementById('autocompleteitem' + n);
}

function autosuggest(event) {
	var keynum;
	if (window.event) { // IE
		keynum = event.keyCode;
	} else if (event.which) { // Netscape/Firefox/Opera
		keynum = event.which;
	}
	if (keynum == 38 || keynum == 40) {
		if (selectedAutocomplete > 0) {
			autocompleteLinkOut(selectedAutocomplete);
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
			var dv = getselectedautocompletediv(selectedAutocomplete);
			autocompleteLinkOver(selectedAutocomplete);
			setKeywords(dv.innerHTML);
		}
		return false;
	}

	if (xmlHttp.readyState != 4 && xmlHttp.readyState != 0)
		return;
	var str = escape(document.getElementById('keyword').value);
	if (str.length == 0) {
		setAutocomplete('');
		return;
	}

	xmlHttp.open("GET", '?s=ossautointernal&q=' + str, true);
	xmlHttp.onreadystatechange = handleAutocomplete;
	xmlHttp.send(null);
	return true;
}

function handleAutocomplete() {
	if (xmlHttp.readyState != 4)
		return;
	var ac = setAutocomplete('');
	var resp = xmlHttp.responseText;
	if (resp == null) {
		return;
	}
	if (resp.length == 0) {
		return;
	}
	var str = resp.split("\n");
	var content = '<div id="autocompletelist">';
	for (i = 0; i < str.length - 1; i++) {
		var j = i + 1;
		content += '<div id="autocompleteitem' + j + '" ';
		content += 'onmouseover="javascript:autocompleteLinkOver(' + j + ');" ';
		content += 'onmouseout="javascript:autocompleteLinkOut(' + j + ');" ';
		content += 'onclick="javascript:setsetKeywords_onClick(this.innerHTML);" ';
		content += 'class="autocomplete_link">' + str[i] + '</div>';
	}
	content += '</div>';
	ac.innerHTML = content;
	selectedAutocomplete = 0;
	autocompleteSize = str.length;
}

function autocompleteLinkOver(n) {
	if (selectedAutocomplete > 0) {
		autocompleteLinkOut(selectedAutocomplete);
	}
	var dv = getselectedautocompletediv(n);
	if(dv != null)
	{
		dv.className = 'autocomplete_link_over';
		selectedAutocomplete = n;
	}
}

function autocompleteLinkOut(n) {
	var dv = getselectedautocompletediv(n);
	if (dv != null) {
		dv.className = 'autocomplete_link';
	}
}
function setsetKeywords_onClick(value) {
	var dv = document.getElementById('keyword');
	if (dv != null) {
		dv.value = value;
		dv.focus();
		setAutocomplete('');
		document.forms['searchformForm'].submit()
		return true;
	}
}
function setKeywords(value) {
	var dv = document.getElementById('keyword');
	if (dv != null) {
		dv.value = value;
		dv.focus();
	}
}