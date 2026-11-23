/* Optimise Alt: saves alt text from the audit table via AJAX. */
( function () {
	'use strict';

	function save( id ) {
		var row = document.querySelector( '.optk-alt-save[data-id="' + id + '"]' );
		var input = document.querySelector( '.optk-alt-input[data-id="' + id + '"]' );
		if ( ! row || ! input || row.disabled ) {
			return;
		}
		var status = row.parentNode.querySelector( '.optk-alt-status' );
		var data = new FormData();
		data.append( 'action', 'optk_alt_save' );
		data.append( 'nonce', optkAlt.nonce );
		data.append( 'id', id );
		data.append( 'alt', input.value );

		row.disabled = true;
		fetch( optkAlt.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res.success ) {
					throw new Error( 'save failed' );
				}
				status.textContent = optkAlt.saved;
				var tr = row.closest( 'tr' );
				tr.style.transition = 'opacity .4s';
				setTimeout( function () { tr.style.opacity = '0'; }, 400 );
				setTimeout( function () { tr.remove(); }, 800 );
			} )
			.catch( function () {
				status.textContent = optkAlt.error;
				row.disabled = false;
			} );
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.optk-alt-save' );
		if ( btn ) {
			save( btn.dataset.id );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Enter' && e.target.classList && e.target.classList.contains( 'optk-alt-input' ) ) {
			e.preventDefault();
			save( e.target.dataset.id );
		}
	} );
}() );
