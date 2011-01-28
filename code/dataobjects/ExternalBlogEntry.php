<?php
/**
 * A blog entry that is imported from an external RSS feed.
 *
 * @package silverstripe-rssconnector
 */
class ExternalBlogEntry extends BlogEntry {

	public static $db = array(
		'Guid'         => 'Varchar(255)',
		'ExternalLink' => 'Varchar(255)'
	);

	public static $has_one = array(
		'Source' => 'RssContentSource'
	);

}

class ExternalBlogEntry_Controller extends BlogEntry_Controller {
}