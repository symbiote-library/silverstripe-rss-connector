<?php
/**
 * @package silverstripe-rssconnector
 */

require_once Director::baseFolder() . '/rssconnector/thirdparty/htmlpurifier/HTMLPurifier.auto.php';

/**
 * Transforms an RSS entry into a local blog entry.
 *
 * @package silverstripe-rssconnector
 */
class RssEntryTransformer implements ExternalContentTransformer {

	protected $importer;

	public function transform($item, $parent, $strategy) {
		$params = $this->importer->getParams();
		$guid   = $item->getGuid();

		$exists = DataObject::get_one('ExternalBlogEntry', sprintf(
			'"Guid" = \'%s\'', Convert::raw2sql($guid)
		));

		if ($exists) {
			if ($strategy == ExternalContentTransformer::DS_SKIP) {
				return;
			}

			if ($strategy == ExternalContentTransformer::DS_OVERWRITE) {
				$entry = $exists;
			}
		}

		if (!isset($entry)) {
			$entry = new ExternalBlogEntry();
		}

		$entry->Guid            = $guid;
		$entry->ExternalLink    = $item->Link;
		$entry->Title           = $item->Title;
		$entry->ParentID        = $parent->ID;
		$entry->Date            = $item->Date;
		$entry->Author          = $item->AuthorName;
		$entry->ProvideComments = isset($params['ProvideComments']);

		$purifier = new HTMLPurifier();
		$entry->Content = $purifier->purify($item->Content);

		if (isset($params['ImportCategories'])) {
			$labels = $item->getCategories()->map('Term', 'Label');
			$tags   = array();

			if ($params['UnknownCategories'] == 'skip') {
				foreach ($labels as $k => $label) {
					$exists = DB::query(sprintf(
						'SELECT 1 FROM "BlogEntry" WHERE "Tags" LIKE \'%%%s%%\'',
						Convert::raw2sql($label)
					));

					if (!$exists->column()) {
						unset($labels[$k]);
					}
				}
			}

			$tags = implode(', ', $labels);

			if ($params['ExtraTags']) {
				$tags .= ', ' . $params['ExtraTags'];
			}

			$entry->Tags = trim(trim($tags), ',');
		}

		$entry->write();

		if (isset($params['PublishPosts'])) {
			$entry->publish('Stage', 'Live');
		}
	}

	public function setImporter(RssContentImporter $importer) {
		$this->importer = $importer;
	}

}