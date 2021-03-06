<?php
	$selectedLang = empty($params['wikiLanguage']) ? $wg->LanguageCode : $params['wikiLanguage'];
?>
<section id="CreateNewWiki">
	<h1><?= wfMessage('cnw-title')->escaped() ?></h1>

	<ol class="steps">
		<li id="NameWiki" class="step">
			<h2><?= wfMessage('cnw-name-wiki-headline')->escaped() ?></h2>
			<p class="creative"><?= wfMessage('cnw-name-wiki-creative')->escaped() ?></p>

			<form name="label-wiki-form">
				<h3><?= wfMessage('cnw-name-wiki-label')->escaped() ?></h3>
				<span class="wiki-name-status-icon status-icon"></span>
				<input type="text" name="wiki-name" value="<?= empty($params['wikiName']) ? '' : $params['wikiName'] ?>"> <?= wfMessage('cnw-name-wiki-wiki')->escaped() ?>
				<div class="wiki-name-error error-msg"></div>
				<h3 dir="ltr"><?= wfMessage('cnw-name-wiki-domain-label')->escaped() ?></h3>

				<div class="wiki-domain-container">
					<span class="domain-status-icon status-icon"></span>
					<span class="domain-country"><?= empty($selectedLang) || $selectedLang === 'en' ? '' : $selectedLang.'.' ?></span>
					<?= wfMessage('cnw-name-wiki-language')->escaped() ?>
					<input type="text" name="wiki-domain" value="<?= empty($params['wikiDomain']) ? '' : $params['wikiDomain'] ?>"> <?= wfMessage('cnw-name-wiki-domain')->escaped() ?>
				</div>
				<div class="wiki-domain-error error-msg"></div>

				<div class="language-default">
					<?= wfMessage('cnw-desc-default-lang', Language::getLanguageName($selectedLang) )->escaped() ?> - <a href="#" id="ChangeLang"><?= wfMessage('cnw-desc-change-lang')->escaped() ?></a>
				</div>

				<div class="language-choice">
					<h3><?= wfMessage('cnw-desc-lang')->escaped() ?></h3>
					<select name="wiki-language">

					<? $isSelected = false; ?>
					<? if (!empty($aTopLanguages) && is_array($aTopLanguages)) : ?>
						<optgroup label="<?= wfMessage('autocreatewiki-language-top', count($aTopLanguages))->escaped() ?>">

							<? foreach ($aTopLanguages as $sLang) :
								$selected = '';
								if ( empty($isSelected) && $sLang == $selectedLang ) {
									$isSelected = true;
									$selected = ' selected="selected"';
								}
							?>
								<option value="<?=$sLang?>" <?=$selected?>><?=$sLang?>: <?=$aLanguages[$sLang]?></option>
							<? endforeach ?>
						</optgroup>
					<? endif ?>

					<? if (!empty($aLanguages) && is_array($aLanguages)) : ?>
						<optgroup label="<?= wfMessage('autocreatewiki-language-all')->escaped() ?>">
						<? ksort($aLanguages);
						foreach ($aLanguages as $sLang => $sLangName) :
							$selected = "";
							if ( empty($isSelected) && ( ( isset($params['wiki-language'] ) && ( $sLang == $params['wiki-language'] ) ) || ( !isset($params['wiki-language']) && ( $sLang == $selectedLang ) ) ) ) :
								$isSelected = true;
								$selected = ' selected="selected"';
							endif; ?>
							<option value="<?=$sLang?>" <?=$selected?>><?=$sLang?>: <?=$sLangName?></option>
						<? endforeach ?>
						</optgroup>
					<? endif; ?>

					</select>
				</div>
				<nav class="next-controls">
					<span class="submit-error error-msg"></span>
					<input type="button" value="<?= wfMessage('cnw-next')->escaped() ?>" class="next">
				</nav>
			</form>
		</li>

		<? if ( !$isUserLoggedIn ): ?>
		<li id="UserAuth" class="step">
			<h2 class="headline"><?= wfMessage('cnw-userauth-headline')->escaped() ?></h2>
			<p class="creative"><?= wfMessage('cnw-userauth-creative')->escaped() ?></p>
			<div class="signup-loginmodal"><?= F::app()->sendRequest('UserLoginSpecial', 'modal') ?></div>
			<div class="signup-marketing">
				<h3><?= wfMessage('cnw-userauth-marketing-heading')->escaped() ?></h3>
				<p><?= wfMessage('cnw-userauth-marketing-body')->parse() ?></p>
				<form method="post" action="<?= $signupUrl ?>" id="SignupRedirect">
					<input type="hidden" name="returnto" value="">
					<input type="hidden" name="redirected" value="true">
					<input type="hidden" name="uselang" value="<?= $params['wikiLanguage'] ?>">
					<input type="submit" value="<?= wfMessage('cnw-userauth-signup-button')->escaped() ?>">
				</form>
			</div>
		</li>
		<? endif; // if isLoggedIn ?>
		<li id="DescWiki" class="step">
			<h2><?= wfMessage('cnw-desc-headline') ?></h2>
			<p class="creative"><?= wfMessage('cnw-desc-creative')->escaped() ?></p>
			<form name="desc-form" class="clearfix">
				<textarea id="Description" placeholder="<?= wfMessage('cnw-desc-placeholder')->escaped() ?>"></textarea>
				<ol>
					<li>
						<?= wfMessage('cnw-desc-tip1')->escaped() ?>
						<div class="tip-creative"><?= wfMessage('cnw-desc-tip1-creative')->escaped() ?></div>
					</li>
					<li>
						<?= wfMessage('cnw-desc-tip2')->escaped() ?>
						<div class="tip-creative"><?= wfMessage('cnw-desc-tip2-creative')->escaped() ?></div>
					</li>
				</ol>

		        <div class="checkbox" id="all-ages-div" <?php echo empty($selectedLang) || $selectedLang === $params['LangAllAgesOpt'] ? '':'style=display:none' ?> >
					<input type="checkbox" name="all-ages" value="1">
					<?= $app->renderView(
						'WikiaStyleGuideTooltipIcon',
						'index',
						[
							'text' => wfMessage('cnw-desc-all-ages')->escaped(),
							'tooltipIconTitle' => wfMessage('cnw-desc-tip-all-ages')->plain(),
						]
					);
					?>
				</div>

				<!-- Hub Category / Vertical -->
				<div class="select-container">
					<h3><?= wfMessage('cnw-desc-select-vertical')->escaped() ?></h3>
					<select name="wiki-vertical">
						<option value="-1"><?= wfMessage('cnw-desc-select-one')->escaped() ?></option>
				<?php
					foreach ($verticals as $vertical) {
				?>
						<option
							value="<?= $vertical['id'] ?>"
							data-short="<?= $vertical['short'] ?>"
							data-categoriesset="<?= $vertical['categoriesSet'] ?>">
							<?= $vertical['name'] ?>
						</option>
				<?php
					}
				?>
					</select>
				</div>

				<!-- Additional Categories -->
				<div class="select-container categories-sets">
					<h3><?= wfMessage('cnw-desc-select-categories')->escaped() ?></h3>
			<?php
				foreach ($categoriesSets as $setId => $categoriesSet) {
			?>

					<div class="categories-set" id="categories-set-<?= $setId ?>">
				<?php
					foreach ($categoriesSet as $category) {
				?>
						<label><input type="checkbox" value="<?= $category['id'] ?>" data-short="<?= $category['short'] ?>"><span><?= $category['name'] ?></span></label>
				<?php
					}
				?>
					</div>
			<?php
				}
			?>
				</div>

				<nav class="back-controls">
					<input type="button" value="<?= wfMessage('cnw-back')->escaped() ?>" class="secondary back">
				</nav>
				<nav class="next-controls">
					<span class="submit-error error-msg"></span>
					<input type="button" value="<?= wfMessage('cnw-next')->escaped() ?>" class="next">
				</nav>
			</form>
		</li>
		<li id="ThemeWiki" class="step">
			<h2><?= wfMessage('cnw-theme-headline')->escaped() ?></h2>
			<p class="creative"><?= wfMessage('cnw-theme-creative')->escaped() ?></p>
			<?= F::app()->renderView('ThemeDesigner', 'ThemeTab') ?>
			<p class="instruction creative"><?= wfMessage('cnw-theme-instruction')->escaped() ?></p>
			<nav class="next-controls">
				<span class="submit-error finish-status"></span>
				<input type="button" value="<?= wfMessage('cnw-next')->escaped() ?>" class="next" disabled>
			</nav>
		</li>
	</ol>
	<ul id="StepsIndicator">
		<?php
			$steps = $isUserLoggedIn ? 4 : 5;
			$active = empty($currentStep) ? 1 : 3;
			for($i = 0; $i < $steps; $i++) {
		?>
			<li class="step<?= $active > 0 ? ' active' : '' ?>"></li>
		<?php
				$active--;
			}
		?>
	</ul>
</section>
<script>
	window.WikiBuilderCfg = <?= json_encode( $wikiBuilderCfg ) ?>;
	var themes = <?= json_encode($wg->OasisThemes) ?>;
	var applicationThemeSettings = <?= json_encode($applicationThemeSettings) ?>;
</script>
