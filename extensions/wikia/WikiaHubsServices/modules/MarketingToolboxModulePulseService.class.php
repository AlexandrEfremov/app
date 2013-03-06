<?
class MarketingToolboxModulePulseService extends MarketingToolboxModuleService {
	const MODULE_ID = 2;

	protected function getFormFields() {
		return array(
			'boardUrl' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-wikiurl'),
				'validator' => new WikiaValidatorToolboxUrl(
					array(),
					array(
						'wrong' => 'marketing-toolbox-validator-wrong-url'
					)
				),
				'attributes' => array(
					'class' => 'wikiaUrl'
				)
			),
			'boardTitle' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-topic'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'stat1' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-stat1'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'stat2' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-stat2'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'stat3' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-stat3'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'number1' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-number1'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'number2' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-number2'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),
			'number3' => array(
				'label' => $this->wf->msg('marketing-toolbox-hub-module-pulse-number3'),
				'validator' => new WikiaValidatorString(
					array(
						'required' => true,
						'min' => 1
					),
					array('too_short' => 'marketing-toolbox-validator-string-short')
				),
				'attributes' => array(
					'class' => 'required'
				)
			),

		);
	}

	public function renderEditor($data) {
		return parent::renderEditor($data);
	}

	public function filterData($data) {
		$data = parent::filterData($data);

		if (!empty($data['boardUrl'])) {
			$data['boardUrl'] = $this->addProtocolToLink($data['boardUrl']);
		}

		return $data;
	}

	public function getStructuredData($data) {
		return array();
	}
}
