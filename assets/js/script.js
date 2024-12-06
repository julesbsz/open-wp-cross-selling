document.addEventListener("DOMContentLoaded", () => {
	console.log("Open WP Cross-selling JS loaded.");

	let drawerOpened = true;

	const backdrop = document.querySelector(".owcs-backdrop");
	const drawer = document.getElementById("owcs-drawer");
	const drawerCloseBtn = document.getElementById("owcs-close-btn");

	if (document.cookie.indexOf("owcs_added_to_cart=1") !== -1) {
		drawerOpened = true;
		backdrop.dataset.opened = true;

		// Delete cookie after use
		document.cookie = "owcs_added_to_cart=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;";
	}

	backdrop.addEventListener("click", closeDrawer);
	drawerCloseBtn.addEventListener("click", closeDrawer);

	drawer.addEventListener("click", (event) => {
		event.stopPropagation();
	});

	function closeDrawer() {
		if (drawerOpened) {
			drawerOpened = false;
			backdrop.dataset.opened = false;
		}
	}
});
