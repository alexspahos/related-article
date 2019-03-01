<?php
// no direct access
defined( '_JEXEC' ) or die;

/**
 * Plugin to enable loading a related article into another article
 * It uses the {relatedarticle xxx} syntax
 *
 */
class plgContentRelatedarticle extends JPlugin {
	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Plugin method with the same name as the event will be called automatically.
	 */
	 function onContentPrepare($context, &$article, &$params, $page = 0) {

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, 'relatedarticle') === false) {
			return true;
		}

		// Expression to search for {relatedarticle xxx}
		$regex = '/{relatedarticle\s(.*?)}/i';

		// Find all instances of plugin and put in $matches
		// $matches[0][0] is full pattern match, $matches[0][1] is the artcle id
		preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

		// if no matches, skip this
		if ($matches) {
			$output = $this->_loadarticle($matches[0][1]);
			
			$article->text = preg_replace($matches[0][0], addcslashes($output, '\\$'), $article->text);
			$article->text = preg_replace("/{|}/","",$article->text);
		}
	}

	/**
	 * Loads and renders article info
	 *
	 * @param   string  $id  The article id
	 *
	 * @return  mixed
	 *
	 */
	protected function _loadarticle($articleId) {
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select all articles from the content table.
		$query->select($db->quoteName(array('title', 'catid', 'images')));
		$query->from($db->quoteName('#__content'));
		$query->where($db->quoteName('id')." = ".$db->quote($articleId));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects.
		$articles = $db->loadObjectList();

		foreach ($articles as $article) {
			$articleImages = json_decode($article->images);
			$articleUrl = JRoute::_(ContentHelperRoute::getArticleRoute($articleId, $article->catid));
		}

		$html  = '<div class="article-inline-position">';
		$html .= '<div class="media">';
		if ($articleImages) {
			$html .= '<div class="media-left">';	
			$html .= '<a href="' . $articleUrl . '" class="media-left-figure">';
			$html .= '<i class="thumbnail-img" style="background-image: url(' . $articleImages->image_intro . ');"></i>';
			$html .= '</a>';
			$html .= '</div>';
		}
		$html .= '<div class="media-body">';
		$html .= '<p class="block-title">ΔΙΑΒΑΣΤΕ ΣΧΕΤΙΚΑ</p>';
		$html .= '<h4 class="article-title"><a href="'. $articleUrl .'">'. $article->title .'</a></h4>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
?>