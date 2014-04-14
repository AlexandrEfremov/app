<?php

class WikiImageRowHelper {

	public static function parseWikiImageRow($row) {
		return new self($row);
	}
	public $name, $index, $reviewed, $review_status;

	function __construct($row) {
		$this->name = $row->image_name;
		$this->index = $row->image_index;
		$this->reviewed = $row->image_reviewed;
		$this->review_status = intval($row->image_review_status);
	}

	public function asArray(){
		return array(
			'image_name' => $this->name,
			'image_index' => intval($this->index),
			'image_reviewed' => $this->reviewed,
			'image_status' => $$this->review_status
		);
	}
}
