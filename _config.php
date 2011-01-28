<?php
/**
 * @package silverstripe-rssconnector
 */

if (!class_exists('BlogEntry')) {
	throw new Exception('The RSS connector module requires the blog module.');
}