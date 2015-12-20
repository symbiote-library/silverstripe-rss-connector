<?php
/**
 * Imports the entries from an RSS feed source.
 *
 * @package silverstripe-rssconnector
 */
class RssContentImporter extends ExternalContentImporter
{

    public function __construct()
    {
        $transformer = new RssEntryTransformer();
        $transformer->setImporter($this);

        $this->contentTransforms['entry'] = $transformer;
    }

    public function getExternalType($item)
    {
        return 'entry';
    }
}
