jQuery(function() {
	jQuery(".widget-latest-github-commits-project-choice").bind("change", function() {
		var display = (this.value == "all") ? "none" : "block";
		jQuery(".widget-latest-github-commits-project-choice-specific").css("display", display);
	});
});