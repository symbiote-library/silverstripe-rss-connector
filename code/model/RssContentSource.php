<?php
/**
 * @package silverstripe-rsscontent
 */

require_once 'simplepie/simplepie.inc';

/**
 * An external RSS feed that content can be imported from.
 *
 * @package silverstripe-rsscontent
 */
class RssContentSource extends ExternalContentSource {

	public static $db = array(
		'Url' => 'Varchar(255)'
	);

	protected $client;

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		Requirements::css('rssconnector/css/RssContentAdmin.css');

		$fields->addFieldToTab(
			'Root.Main', new TextField('Url', 'RSS/Atom Feed URL'),
			'ShowContentInMenu'
		);

		if (!$this->Url || !$client = $this->getClient()) {
			return $fields;
		}

		if ($client->error) {
			$message = 'The feed URL entered appears to be invalid, or could not be loaded.';
			$error   = $client->error;

			$fields->addFieldToTab(
				'Root.Main',
				new LiteralField('InvalidFeed',
					sprintf('<p id="invalid-feed">%s<span>%s</span></p>', $message, $error)),
				'Name');

			return $fields;
		}

		return $fields;
	}

	/**
	 * Attempts to get an RSS content item by GUID.
	 *
	 * @param  string|int $id
	 * @return RssContentItem
	 */
	public function getObject($id) {
		$id    = $this->decodeId($id);
		$items = $this->getClient()->get_items();

		foreach ($items as $item) {
			if ($item->get_id() == $id) return new RssContentItem($this, $item);
		}
	}

	public function getRoot() {
		return $this;
	}

	public function stageChildren() {
		$items    = $this->getClient()->get_items();
		$children = new DataObjectSet();

		foreach ($items as $item) {
			$children->push(new RssContentItem($this, $item));
		}

		return $children;
	}

	/**
	 * @return SimplePie
	 */
	public function getClient() {
		if (!$this->client) {
			$this->client = new SimplePie($this->Url);
			$this->client->enable_cache(true);
			$this->client->set_cache_location(TEMP_FOLDER);
		}

		return $this->client;
	}

}