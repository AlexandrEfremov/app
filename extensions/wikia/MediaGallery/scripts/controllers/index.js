require(['mediaGallery.views.gallery'], function (Gallery) {
	'use strict';
	$(function () {
		var $galleries = $('.media-gallery-wrapper'),
			// get data from script tag in DOM
			data = Wikia.mediaGalleryData || [];

		// If there's no galleries on the page, we're done.
		if (!data.length) {
			return;
		}

		$.each($galleries, function (idx) {
			var $this = $(this),
				origVisibleCount = $this.data('visible-count') || 8,
				gallery;

			gallery = new Gallery({
				$el: $('<div></div>'),
				$wrapper: $this,
				model: {
					media: data[idx]
				},
				origVisibleCount: origVisibleCount
			});
			$this.append(gallery.render(origVisibleCount).$el);

			if (gallery.$toggler) {
				$this.append(gallery.$toggler);
			}

			gallery.rendered = true;
			gallery.$el.trigger('galleryInserted');
		});
	});
});
