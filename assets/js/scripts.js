/**
 * Scripts file for cf7as plugin
 *
 * by bishoy.me
 */

jQuery(document).ready(function() {

	jQuery.each( cf7as.forms, function( index, value ){
		jQuery('div[id^="wpcf7-f'+value+'"]').find('form').sisyphus({
			excludeFields: jQuery( ".wpcf7-acceptance, .ascf7-excluded" ),
			autoRelease: cf7as.auto_release,
			locationBased: cf7as.location_based,
		});
	});
});

if ( cf7as.fb ) {

	if ( cf7as.fb_text ) {
		var fb_text = cf7as.fb_text;
	} else {
		var fb_text = 'Fill with Facebook';
	}

	jQuery.each( cf7as.fb_forms, function( index, value ){
		if ( cf7as.fb_above ) {
			jQuery('div[id^="wpcf7-f'+value+'"]').prepend('<div class="cf7as-fill-with-fb cf7as-link-top"><a href="#" onClick="cf7as_fill_with_fb('+value+'); return false;">' + fb_text + '</a></div>');
		} else {
			jQuery('div[id^="wpcf7-f'+value+'"]').append('<div class="cf7as-fill-with-fb cf7as-link-bottom"><a href="#" onClick="cf7as_fill_with_fb('+value+'); return false;">' + fb_text + '</a></div>');
		}
	});

	function cf7as_fill_with_fb( form_id ) {

		FB.login(function(response) {

	      if (response.authResponse) {
	          FB.api('/me', {fields: 'first_name,last_name,email'}, function(response) {
	          	
	          	jQuery('div[id^="wpcf7-f'+form_id+'"]').find('.cf7as-fb-first-name').val(response.first_name);
	          	jQuery('div[id^="wpcf7-f'+form_id+'"]').find('.cf7as-fb-last-name').val(response.last_name);
	          	jQuery('div[id^="wpcf7-f'+form_id+'"]').find('.cf7as-fb-email').val(response.email);

	          });

	      } else {
	          //user hit cancel button
	          console.log('User cancelled login or did not fully authorize.');
	      }
	  }, {
	      scope: 'public_profile,email'
	  });
	}
}