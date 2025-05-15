jQuery(document).ready(function ($) {
	// Use a more specific selector that only targets buttons within OWCS plugin settings tab
	$('body.woocommerce_page_wc-settings.tab-cross_selling_drawer button[name="save"]').on("click", function (e) {
		e.preventDefault();
		const currentSection = new URLSearchParams(window.location.search).get("section");

		if (currentSection === "create") {
			const $form = $("#owcs-create-preset-form");
			const $submitButton = $(this);

			$submitButton.prop("disabled", true);

			$.ajax({
				url: owcsAdmin.ajaxurl,
				type: "POST",
				data: {
					action: "owcs_save_preset",
					nonce: owcsAdmin.nonce,
					name: $("#preset_name").val(),
					products: $("#preset_products").val(),
				},
				success: function (response) {
					console.log("Response:", response);
					if (response.success) {
						showNotice(owcsAdmin.strings.presetSaved);
						$form[0].reset();
						$("#preset_products").val(null).trigger("change");
					} else {
						showNotice(response.data || owcsAdmin.strings.error, "error");
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("Error:", {
						status: textStatus,
						error: errorThrown,
						response: jqXHR.responseText,
					});
					showNotice(owcsAdmin.strings.error, "error");
				},
				complete: function () {
					$submitButton.prop("disabled", false);
				},
			});
		} else if (currentSection === "list") {
			const $submitButton = $(this);
			$submitButton.prop("disabled", true);

			const updates = [];
			$(".owcs-preset-card").each(function () {
				const $card = $(this);
				const presetId = $card.data("preset-id");
				const products = $card.find("select").val() || [];

				console.log("Updating preset:", {
					presetId: presetId,
					products: products,
				});

				updates.push(
					$.ajax({
						url: owcsAdmin.ajaxurl,
						type: "POST",
						data: {
							action: "owcs_update_preset",
							preset_id: presetId,
							products: products,
							nonce: owcsAdmin.nonce,
						},
					})
				);
			});

			Promise.all(updates)
				.then(function (responses) {
					console.log("Update responses:", responses);
					const allSuccess = responses.every((r) => r.success);
					if (allSuccess) {
						showNotice(owcsAdmin.strings.presetSaved);
					} else {
						showNotice(owcsAdmin.strings.error, "error");
					}
				})
				.catch(function (error) {
					console.error("Update error:", error);
					showNotice(owcsAdmin.strings.error, "error");
				})
				.finally(function () {
					$submitButton.prop("disabled", false);
				});
		} else if (currentSection === "settings") {
			const $submitButton = $(this);
			$submitButton.prop("disabled", true);

			$.ajax({
				url: owcsAdmin.ajaxurl,
				type: "POST",
				data: {
					action: "owcs_save_settings",
					nonce: owcsAdmin.nonce,
					display_short_desc: $("#display_short_desc").is(":checked") ? "yes" : "no",
					short_desc_max_chars: $("#short_desc_max_chars").val(),
				},
				success: function (response) {
					console.log("Response:", response);
					if (response.success) {
						showNotice(response.data.message);
					} else {
						showNotice(response.data || owcsAdmin.strings.error, "error");
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.log("Error:", {
						status: textStatus,
						error: errorThrown,
						response: jqXHR.responseText,
					});
					showNotice(owcsAdmin.strings.error, "error");
				},
				complete: function () {
					$submitButton.prop("disabled", false);
				},
			});
		}
	});

	// Gestion de la suppression des presets
	$(".owcs-delete-preset").on("click", function (e) {
		e.preventDefault();
		if (!confirm(owcsAdmin.strings.confirmDelete)) return;

		const $button = $(this);
		const $card = $button.closest(".owcs-preset-card");

		$.ajax({
			url: owcsAdmin.ajaxurl,
			type: "POST",
			data: {
				action: "owcs_delete_preset",
				preset_id: $button.data("preset-id"),
				nonce: $button.data("nonce"),
			},
			success: function (response) {
				if (response.success) {
					$card.fadeOut(400, function () {
						$(this).remove();
					});
				}
			},
		});
	});

	// Fonction utilitaire pour afficher les notifications
	function showNotice(message, type = "success") {
		const $notice = $("<div>").addClass("owcs-notice").addClass(type).text(message);

		$(".wrap").first().prepend($notice);

		setTimeout(() => {
			$notice.fadeOut(100, function () {
				$(this).remove();
			});
		}, 3000);
	}
});
