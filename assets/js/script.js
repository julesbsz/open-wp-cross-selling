document.addEventListener("DOMContentLoaded", () => {
	console.log("Open WP Cross-selling JS loaded.");

	let drawerOpened = true;

	const backdrop = document.querySelector(".owcs-backdrop");
	const drawer = document.getElementById("owcs-drawer");
	const drawerCloseBtn = document.getElementById("owcs-close-btn");

	if (!backdrop || !drawer) return;

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

	// Gestion de la redirection vers la page produit
	document.querySelectorAll(".owcs-product-container").forEach((card) => {
		card.addEventListener("click", function (e) {
			console.log("product clicked");
			const productLink = card.dataset.link;
			if (!productLink) return;

			window.location.href = productLink;
		});
	});

	// Gestion de l'ajout au panier des produits dans le drawer
	document.querySelectorAll(".owcs-product-button-secondary").forEach((button) => {
		button.addEventListener("click", async function (e) {
			console.log("add to cart button clicked");

			e.preventDefault();
			e.stopPropagation();

			const productId = this.dataset.productId;
			const nonce = this.dataset.nonce;

			// Ajouter la classe loading
			this.classList.add("loading");

			try {
				const response = await fetch(wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%", "add_to_cart"), {
					method: "POST",
					headers: {
						"Content-Type": "application/x-www-form-urlencoded",
					},
					body: new URLSearchParams({
						product_id: productId,
						quantity: 1,
						security: nonce,
					}),
				});

				const data = await response.json();

				if (data.error) {
					throw new Error(data.error);
				}

				// Mettre à jour les fragments du panier
				if (data.fragments) {
					jQuery.each(data.fragments, function (key, value) {
						jQuery(key).replaceWith(value);
					});
				}

				// Déclencher l'événement pour mettre à jour le mini-panier
				jQuery(document.body).trigger("added_to_cart", [data.fragments, data.cart_hash]);

				// Remplacer le bouton par un lien vers le panier
				const viewCartBtn = document.createElement("a");
				viewCartBtn.href = wc_add_to_cart_params.cart_url;
				viewCartBtn.className = "wp-element-button owcs-product-button-secondary added";
				viewCartBtn.textContent = owcsTranslations.viewCart;
				this.replaceWith(viewCartBtn);
			} catch (error) {
				console.error("Error:", error);
				// Optionnel : Afficher un message d'erreur à l'utilisateur
			} finally {
				// Retirer la classe loading dans tous les cas
				this.classList.remove("loading");
			}
		});
	});
});
