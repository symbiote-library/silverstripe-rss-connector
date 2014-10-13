<?php
/**
 * An RSS entry from an external RSS feed.
 *
 * @package silverstripe-rssconnector
 */
class RssContentItem extends ExternalContentItem {

	protected $item;
	protected $categories;

	/**
	 * @param RssContentSource $source
	 * @param SimplePie_Item $item
	 */
	public function __construct($source = null, $item = null) {
		if (is_object($item)) {
			$this->item = $item;
			$item = $item->get_id();
		}

		parent::__construct($source, $item);
	}

	public function init() {
		$this->Title     = $this->item->get_title();
		$this->Link      = $this->item->get_link();
		$this->Date      = $this->item->get_date('Y-m-d H:i:s');
		$this->Content   = $this->item->get_content();

		if ($author = $this->item->get_author()) {
			$this->AuthorName  = $author->get_name();
			$this->AuthorEmail = $author->get_email();
			$this->AuthorLink  = $author->get_link();
		}

		$this->categories = new ArrayList();
		
		$categories = @$this->item->get_categories();
		//$categories = self::simplepie_get_categories($this->item);

		if ($categories) foreach ($categories as $category) {
			$this->categories->push(new ArrayData(array(
				'Label'  => $category->get_label(),
				'Term'   => $category->get_term(),
				'Scheme' => $category->get_scheme()
			)));
		}

		$this->Latitude  = $this->item->get_latitude();
		$this->Longitude = $this->item->get_longitude();
	}

	public function numChildren() {
		return 0;
	}

	public function stageChildren($showAll = false) {
		return new ArrayList();
	}

	public function getType() {
		return 'file';
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$categoriesString = '';
		foreach ($this->categories as $cat) {
			//$categoriesString .= "<li>$cat->Label / $cat->Term / $cat->Scheme</li> \n";
			$categoriesString .= "<li>$cat->Label</li> \n";
		}
		$categoriesString = "<ul>$categoriesString</ul>";
		
		
		$fields->addFieldsToTab('Root.Main', array(
			new HeaderField('CategoriesHeader', 'Categories', 4),
			new LiteralField('Categories', $categoriesString)
		));

		$fields->addFieldsToTab('Root.Location', array(
			new ReadonlyField('Latitude', null, $this->Latitude),
			new ReadonlyField('Longitude', null, $this->Longitude)
		));

		$fields->addFieldToTab('Root.Behaviour', new ReadonlyField(
			'ShowInMenus', null, $this->ShowInMenus
		));

		return $fields;
	}

	public function getGuid() {
		return $this->externalId;
	}

	public function getCategories() {
		return $this->categories;
	}

	public function canImport() {
		return false;
	}



	//amended simple pie method for proper category import
	//static function simplepie_get_categories($item) {
	//	
	//	$categories = array();

	//	foreach ((array) $item->get_item_tags(SIMPLEPIE_NAMESPACE_ATOM_10, 'category') as $category)
	//	{
	//		$term = null;
	//		$scheme = null;
	//		$label = null;
	//		if (isset($category['attribs']['']['term']))
	//		{
	//			$term = $item->sanitize($category['attribs']['']['term'], SIMPLEPIE_CONSTRUCT_TEXT);
	//		}
	//		if (isset($category['attribs']['']['scheme']))
	//		{
	//			$scheme = $item->sanitize($category['attribs']['']['scheme'], SIMPLEPIE_CONSTRUCT_TEXT);
	//		}
	//		if (isset($category['attribs']['']['label']))
	//		{
	//			$label = $item->sanitize($category['attribs']['']['label'], SIMPLEPIE_CONSTRUCT_TEXT);
	//		}
	//		$categories[] =& new $item->feed->category_class($term, $scheme, $label);
	//	}
	//	foreach ((array) $item->get_item_tags('', 'category') as $category)
	//	{
	//		$categories[] =& new $item->feed->category_class($item->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
	//	}
	//	foreach ((array) $item->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject') as $category)
	//	{
	//		$categories[] =& new $item->feed->category_class($item->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
	//	}
	//	foreach ((array) $item->get_item_tags(SIMPLEPIE_NAMESPACE_DC_10, 'subject') as $category)
	//	{
	//		$categories[] =& new $item->feed->category_class($item->sanitize($category['data'], SIMPLEPIE_CONSTRUCT_TEXT), null, null);
	//	}
	//
	//	return $categories;
	//}


}