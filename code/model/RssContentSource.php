<?php
/**
 * @package silverstripe-rsscontent
 */

/**
 * An external RSS feed that content can be imported from.
 *
 * @package silverstripe-rsscontent
 */
class RssContentSource extends ExternalContentSource {

	const DEFAULT_CACHE_LIFETIME = 3600;

	private static $db = array(
		'Url'           => 'Varchar(255)',
		'CacheLifetime' => 'Int'
	);

	private static $defaults = array(
		'CacheLifetime' => self::DEFAULT_CACHE_LIFETIME
	);

	private static $icon = 'rssconnector/images/rssconnector';

	protected $client;

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		Requirements::css('rssconnector/css/RssContentAdmin.css');

		$fields->addFieldToTab(
			'Root.Main',
			new TextField('Url', 'RSS/Atom Feed URL'), 'ShowContentInMenu');

		$fields->addFieldToTab(
			'Root.Advanced',
			new NumericField('CacheLifetime', 'Cache Lifetime (in seconds)'));

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

		$fields->addFieldsToTab('Root.Main', array(
			new HeaderField('FeedDetailsHeader', 'Feed Details'),
			new ReadonlyField('FeedTitle', 'Title', $client->get_title()),
			new ReadonlyField('FeedDescription', 'Description', $client->get_description()),
			new ReadonlyField('FeedLink', 'Link', $client->get_link())
		));

		// If the blog module doesn't exist, we can't run imports.
		if (!class_exists('BlogEntry')) {
			$fields->addFieldToTab('Root.Import', new LiteralField(
				'RequiresBlogImport', '<p>The RSS connector requires the blog '
				. 'module to import RSS items.</p>'
			));

			return $fields;
		}

		$fields->addFieldsToTab('Root.Import', array(
			new HeaderField('PostImportHeader', 'Post Import Settings'),
			new CheckboxField('PublishPosts', 'Publish imported posts?', true),
			new CheckboxField('ProvideComments', 'Allow comments on imported posts?', true),
			new HeaderField('TagsImportHeader', 'Tags Import Settings'),
			new CheckboxField('ImportCategories', 'Import categories as tags?', true),
			new DropdownField('UnknownCategories', 'Unknown categories', array(
				'create' => 'Have a tag created for them',
				'skip'   => 'Are ignored'
			)),
			new TextField('ExtraTags', 'Tags to include on imported posts (comma separated)'),
			new HeaderField('GeneralImportHeader', 'General Import Settings')
		));

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

	public function stageChildren($showAll = false) {
		$items    = $this->getClient()->get_items();
		$children = new ArrayList();

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
			$this->client = new SimplePie();
			$this->client->set_feed_url($this->Url);
			$this->client->enable_cache(true);
			$this->client->set_cache_duration($this->getCacheLifetime());
			//$this->client->set_cache_duration(0);
			$this->client->set_cache_location(TEMP_FOLDER);
		}

		$this->client->init();

		return $this->client;
	}

	public function getContentImporter($target = NULL) {
		return new RssContentImporter();
	}

	public function allowedImportTargets() {
		return array('sitetree' => true);
	}

	public function canImport() {
		return class_exists('BlogEntry') && $this->Url && !$this->getClient()->error;
	}

	/**
	 * @return int
	 */
	public function getCacheLifetime() {
		return ($t = $this->getField('CacheLifetime')) ? $t : self::DEFAULT_CACHE_LIFETIME;
	}

}