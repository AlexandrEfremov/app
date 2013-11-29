var AdConfig2Late = function (
	// regular dependencies
	log,
	window,

	// AdProviders
	adProviderGamePro,
	adProviderLiftium,
	adProviderNull,
	adProviderSevenOneMedia
) {
	'use strict';

	var logGroup = 'AdConfig2',
		cityLang = window.wgContentLanguage,
		deProvider = window.wgAdDriverUseSevenOneMedia ? adProviderSevenOneMedia : adProviderGamePro,
		liftiumSlotsToShowWithSevenOneMedia = {
			'WIKIA_BAR_BOXAD_1': true,
			'TOP_BUTTON_WIDE': true,
			'TOP_BUTTON_WIDE.force': true
		},
		tryLiftium;

	function getProvider(slot) {
		var slotname = slot[0];

		log('getProvider', 5, logGroup);
		log(slot, 5, logGroup);

		if (slot[2] === 'Liftium') {
			if (adProviderLiftium.canHandleSlot(slot)) {
				return adProviderLiftium;
			}
			log('#' + slotname + ' disabled. Forced Liftium, but it can\'t handle it', 7, logGroup);
			return adProviderNull;
		}

		// First ask GamePro (german lang wiki)
		if (cityLang === 'de') {
			if (slotname === 'PREFOOTER_RIGHT_BOXAD' || slotname === 'LEFT_SKYSCRAPER_3') {
				return adProviderNull;
			}
			if (deProvider.canHandleSlot(slotname)) {
				return deProvider;
			}
		}

		if (window.wgAdDriverUseSevenOneMedia) {
			tryLiftium = liftiumSlotsToShowWithSevenOneMedia[slot[0]];
		} else {
			tryLiftium = true;
		}

		if (tryLiftium && adProviderLiftium.canHandleSlot(slotname)) {
			return adProviderLiftium;
		}

		return adProviderNull;
	}

	return {
		getProvider: getProvider
	};
};
