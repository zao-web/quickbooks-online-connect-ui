window.Zao_QBO_API_Connect_UI = window.Zao_QBO_API_Connect_UI || {};

( function( window, document, $, app, undefined ) {
	'use strict';

	app.cache = function() {
		app.$ = {};

		app.$.input = $( document.getElementById( 'clipboard-redirect-uri' ) ).width( '75%' );
		app.$.input.after( '<button id="copy-clipboard-redirect-uri" class="button-secondary" type="button">' + app.l10n.copy + '</button>' );
		app.$.btn = $( document.getElementById( 'copy-clipboard-redirect-uri' ) );
	};

	app.copyText = function( text ) {
		var success = false;

		var createNode = function( content ) {
			var node = document.createElement( 'pre' );

			node.style.width = '1px';
			node.style.height = '1px';
			node.style.position = 'fixed';
			node.style.top = '5px';
			node.textContent = content;

			return node;
		};

		var copyNode = function( node ) {
			var selection = getSelection();

			selection.removeAllRanges();

			var range = document.createRange();

			range.selectNodeContents( node );
			selection.addRange( range );

			try {
				success = document.execCommand( 'copy' );
			} catch ( err ) {
			}

			selection.removeAllRanges();
		};

		var node = createNode( text );

		document.body.appendChild( node );
		copyNode( node );
		document.body.removeChild( node );

		return success;
	};

	app.maybeCopy = function( evt ) {
		evt.preventDefault();
		var copied = app.copyText( app.$.input.val() );

		if ( copied ) {
			app.$.btn.text( app.l10n.copied );
			setTimeout( function() {
				app.$.btn.text( app.l10n.copy );
			}, 1000 );
		}
	};

	app.init = function() {
		app.cache();

		$( document.body ).on( 'click', '.cmb2-options-page #copy-clipboard-redirect-uri', app.maybeCopy );
	};

	$( app.init );

} )( window, document, jQuery, window.Zao_QBO_API_Connect_UI );
