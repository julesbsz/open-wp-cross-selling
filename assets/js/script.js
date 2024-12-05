jQuery(document).ready(function ($) {
	// If add to cart cookie is detected
	if (document.cookie.indexOf("owcs_added_to_cart=1") !== -1) {
		$("#owcs-aside").addClass("active");

		// Delete cookie after use
		document.cookie = "owcs_added_to_cart=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}
});
