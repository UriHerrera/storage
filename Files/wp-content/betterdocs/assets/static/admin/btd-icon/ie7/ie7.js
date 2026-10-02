/* To avoid CSS expressions while still supporting IE 7 and IE 6, use this script */
/* The script tag referencing this file must be placed before the ending body tag. */

/* Use conditional comments in order to target IE 7 and older:
	<!--[if lt IE 8]><!-->
	<script src="ie7/ie7.js"></script>
	<!--<![endif]-->
*/

(function() {
	function addIcon(el, entity) {
		var html = el.innerHTML;
		el.innerHTML = '<span style="font-family: \'btd-icon\'">' + entity + '</span>' + html;
	}
	var icons = {
		'btd-add-line': '&#xe900;',
		'btd-close-fill': '&#xe901;',
		'btd-copy': '&#xe902;',
		'btd-moon': '&#xe903;',
		'btd-search': '&#xe904;',
		'btd-sun': '&#xe905;',
		'btd-crown': '&#xe906;',
		'btd-lock': '&#xe907;',
		'btd-delete': '&#xe908;',
		'btd-upload': '&#xe909;',
		'btd-tick': '&#xe90a;',
		'btd-duplicate': '&#xe90b;',
		'btd-lock-open': '&#xe90c;',
		'btd-key': '&#xe90d;',
		'btd-arrow-up': '&#xe90e;',
		'0': 0
		},
		els = document.getElementsByTagName('*'),
		i, c, el;
	for (i = 0; ; i += 1) {
		el = els[i];
		if(!el) {
			break;
		}
		c = el.className;
		c = c.match(/btd-[^\s'"]+/);
		if (c && icons[c[0]]) {
			addIcon(el, icons[c[0]]);
		}
	}
}());
