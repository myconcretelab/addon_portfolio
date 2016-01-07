<?php
namespace Concrete\Package\AddonPortfolio\Controller;

use URL;
use File;
use FileSet;
use Permissions;
use \Concrete\Core\File\EditResponse as FileEditResponse;
use \Concrete\Core\Controller\Controller as RouteController;
use FileAttributeKey;
use Loader;
use Core;
use Page;
use stdClass;
use Request;
use Concrete\Core\Area\Layout\Preset\Provider\ThemeProviderInterface;
use Package;
use CollectionAttributeKey;


class AddonPortfolioHelper extends RouteController
{

  public function getPageTags ($pages) {
    $tagsObject = new StdClass();
    $tagsObject->tags = $tagsObject->pageTags = array();
    $ak = CollectionAttributeKey::getByHandle('tags');
    $db = Loader::db();

    foreach ($pages as $key => $page):
    		if ($page->getAttribute('tags')) :

    				$v = array($page->getCollectionID(), $page->getVersionID(), $ak->getAttributeKeyID());
    				$avID = $db->GetOne("SELECT avID FROM CollectionAttributeValues WHERE cID = ? AND cvID = ? AND akID = ?", $v);
    				if (!$avID) continue;

    				$query = $db->GetAll("
    						SELECT opt.value
    						FROM atSelectOptions opt,
    						atSelectOptionsSelected sel

    						WHERE sel.avID = ?
    						AND sel.atSelectOptionID = opt.ID",$avID);

    				foreach($query as $opt) {
    						$handle = preg_replace('/\s*/', '', strtolower($opt['value']));
    						$tagsObject->pageTags[$page->getCollectionID()][] =  $handle ;
                $tagsObject->pageTagsName[$page->getCollectionID()][] =  $opt['value'];
    						$tagsObject->tags[$handle] = $opt['value'];
    				}
    		endif ;
    endforeach;
    return $tagsObject;
  }

  function getClassSettings ($block,$prefix) {
    $styleObject = new StdClass();
    if (is_object($block) && is_object($style = $block->getCustomStyle())) :
			$classes = $style->getStyleSet()->getCustomClass();
			$classesArray = explode(' ', $classes);
			$styleObject->classesArray = $classesArray;
      preg_match('/' . $prefix . '-(\w+)/',$classes,$found);
      return isset($found[1]) ? (int)$found[1] : false;
    endif;
  }

	function getClassSettingsObject ($block, $defaultColumns = 3, $defaultMargin = 10  ) {
		$styleObject = new StdClass();

		if (is_object($block) && is_object($style = $block->getCustomStyle())) :
			// We get string as 'first-class second-class'
			$classes = $style->getStyleSet()->getCustomClass();
			// And get array with each classes : 0=>'first-class', 1=>'second-class'
			$classesArray = explode(' ', $classes);
			$styleObject->classesArray = $classesArray;

			// get Columns number
			preg_match("/(\d)-column/",$classes,$columns);
			$styleObject->columns = isset($columns[1]) ? (int)$columns[1] : (int)$defaultColumns;
			// Get margin number
			// If columns == 1 then we set margin to 0
			// If more columns, set margin to asked or to default.
			preg_match("/carousel-margin-(\d+)/",$classes,$margin);
			$styleObject->margin = $styleObject->columns > 1 ? (isset($margin[1]) ? (int)$margin[1] : (int)$defaultMargin ) : 0 ;
			// Get the 'no-text' class
			// The title is displayed by default
			$styleObject->displayTitle = array_search('no-text',$classesArray) === false;
		else :
			$styleObject->columns = (int)$defaultColumns;
			$styleObject->margin = (int)$defaultMargin;
			$styleObject->classesArray = array();
		endif;

		return $styleObject;

	}

  function getPageListVariables ($b,$controller,$pages,$options = array()) {
    $options = array_merge(array(
                      'type' => 'tiny',
                      'wrapperTag' => 'div',
                      'itemTag' => 'div',
                      'AddInnerDiv' => true,
                      'topicAttributeKeyHandle' => 'project_topics',
                      'alternativeDateAttributeHandle' => 'date',
                      'hideEditMode' => true,
                      'user' => false,
                      'topics' => false,
                      'forcePopup' => false,
                      'slider' => false,
                      'additionalWrapperClasses' => array(),
                      'additionalItemClasses' => array()),
                      $options);

    /*
      Les carousels sont activé par une classe "is-carousel"
        => Ajout de la classe 'slick-wrapper' sur le wrapper
        => Ajout des options slick sous forme Ajax et en temps qu'attribut data du wrapper
      Le masonry est activé par la classe 'is-masonry' , sauf si carousel.
        => Le wrapper contient la classe "masonry-wrapper"
        => Le wrapper contient l'attribut data-gridsizer avec la classe des colonnes
        -- Si pas masonery
          => ajout d'un div.row tous les X
      Les filtre de tags sont activé par une classe "tag-sorting"
        => géré par elements/sortable.php
      Les filtre keywords sont activé par une classe "keywords-sorting"
        => géré par elements/sortable.php
      Le nombre de colonnes pas column-x
      L'absence de marge par "no-gap"
      L'affichage en popup est activé par la classe "popup-link" ou par l'option 'forcePopup'

      Chaque page liste a un wrapper qui portera le nom du fichier en temps que classe
    */

    $ag = \Concrete\Core\Http\ResponseAssetGroup::get();
    $ag->requireAsset('javascript','imagesloaded');
  	$ag->requireAsset('javascript','isotope');
  	$ag->requireAsset('javascript','lazyload');
    $ag->requireAsset('javascript','slick');
    $ag->requireAsset('javascript','utils');

    $ag->requireAsset('css','slick');
    $ag->requireAsset('css','slick-theme');

    $vars = array();
    $c = Page::getCurrentPage();
    $nh = Loader::helper('navigation');
    $vars['th'] = $th = Loader::helper('text');
    $vars['dh'] = $dh = Core::make('helper/date');
    $request = Request::getInstance();
    $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($options['type']);

    $styleObject = $this->getClassSettingsObject($b);
    $tagsObject = $this->getPageTags($pages);

    $displayUser = true;
    $displaytopics = $options['topics'];
    $displayPopup = (in_array('popup-link',$styleObject->classesArray)) || ($options['forcePopup']);
    $isCarousel = in_array('is-carousel',$styleObject->classesArray);
    $isMasonry = in_array('is-masonry',$styleObject->classesArray) && !$isCarousel;
    $isStaticGrid = !$isMasonry && !$isCarousel;

    // Theme related

    $o = new StdClass(); // $this->getOptions();
    $o->carousel_dots = true;
    $o->carousel_arrows = true;
    $vars['o'] = $o;
    $vars['tagsObject'] = $tagsObject;
    $vars['styleObject'] = $styleObject;
    $vars['$masonryWrapperAttributes'] = 'data-gridsizer=".' . $vars['column_class'] . '" data-bid="' . $b->getBlockID() . '"';
    $vars['gap'] = (in_array('no-gap',$styleObject->classesArray)) ? 'no-gap' : 'with-gap';
    $vars['column_class'] = ($styleObject->columns > 3 ? 'col-md-' : 'col-sm-') . intval(12 / $styleObject->columns);
    // carousels
    if ($isCarousel) :
      $slick = new StdClass();
      $slick->slidesToShow = $styleObject->columns;
      $slick->slidesToScroll = $styleObject->columns;
      $slick->margin = $styleObject->margin;
      $slick->dots = (bool)$o->carousel_dots;
      $slick->arrows = (bool)$o->carousel_arrows;
      $slick->infinite = (bool)$o->carousel_infinite;
      $slick->speed = (int)$o->carousel_speed;
      $slick->centerMode = (bool)$o->carousel_centerMode;
      $slick->variableWidth = (bool)$o->carousel_variableWidth;
      $slick->adaptiveHeight = (bool)$o->carousel_adaptiveHeight;
      $slick->autoplay = (bool)$o->carousel_autoplay;
      $slick->autoplaySpeed = (int)$o->carousel_autoplaySpeed;
      $vars['slick'] = $slick;
    endif;

    /***** Block related ****/
    $templateName = $b->getBlockFilename();
    $blockTypeHandle = str_replace('_', '-', $b->getBlockTypeHandle());
    $templateCleanName = str_replace('_', '-', $templateName);
    $vars['includeEntryText'] = ($controller->includeName || $controller->includeDescription || $controller->useButtonForLink) ? true :false;

    // Wrapper classes
    $wrapperClasses[] = 'ccm-' . $blockTypeHandle; // ccm-page-list
    $wrapperClasses[] =  $blockTypeHandle . '-' . $templateCleanName; //-> page-list-portfolio
    $wrapperClasses[] = $templateCleanName; // -> portfolio
    if ($isCarousel) 	$wrapperClasses[] = 'slick-wrapper ';
    if ($isMasonry) 	$wrapperClasses[] = 'masonry-wrapper';
    $wrapperClasses[] = 'wrapper-'. $styleObject->columns . '-column';
    // $wrapperClasses[] = 'row';
    $wrapperClasses[] = (in_array('no-gap',$styleObject->classesArray)) ? 'no-gap' : 'with-gap';
    // Wrapper attributes
    $wrapperAtrtribute[] = 'data-bid="' . $b->getBlockID() . '"';
    if ($isMasonry) $wrapperAtrtribute[] = 'data-gridsizer=".' . $vars['column_class'] . '"';
    if ($isCarousel) $wrapperAtrtribute[] = 'data-slick=\'' . json_encode($slick) . '\'';
    // Finally, wrapper html
    $vars['wrapperOpenTag'] = '<' . $options['wrapperTag'] . ' class="' . implode(' ', array_merge($wrapperClasses,$options['additionalWrapperClasses'])) . '" ' . implode(' ', $wrapperAtrtribute) . '>';
    $vars['wrapperCloseTag'] = '</' . $options['wrapperTag'] . '><!-- end .' . $blockTypeHandle . '-' . $templateCleanName . ' -->';
    // Item classes
    if(!$isCarousel) $itemClasses[] = $vars['column_class'];
    $itemClasses[] = 'item';
    if ($isMasonry) $itemClasses[] = 'masonry-item';
    // itemTag
    $itemAttributes = array();
    if($isCarousel) $itemAttributes[] = (in_array('no-gap',$styleObject->classesArray) ? '' : 'style="margin:0 15px"');

    /*****  Page related -- *****/

    foreach ($pages as $key => $page):
      $page->mclDetails = array();
      $externalLink = $page->getAttribute('external_link');
      $page->mclDetails['url'] = $externalLink ? $externalLink : $nh->getLinkToCollection($page);
      $page->mclDetails['popupClassLauncher'] = '';
      $page->mclDetails['render'] = false;
      $page->mclDetails['popup'] = false;

      // Popup
      if ($page->getPageTemplateHandle() == 'one_page_details' && $displayPopup):
        $v = $page->mclDetails['v'] = $page->getController()->getViewObject();
        $page->isPopup = true;
        $page->mclDetails['url'] = "#mcl-popup-{$page->getCollectionID()}";
        $page->mclDetails['popupClassLauncher'] = 'open-popup-link';
        $request->setCurrentPage($page);
        $page->mclDetails['render'] = $v->render("one_page_details");
        $page->mclDetails['popup'] = '<div class="white-popup mfp-hide large-popup" id="mcl-popup-' . $page->getCollectionID() .'">' . $page->mclDetails['render'] . '</div>';
        $request->setCurrentPage($c);
      endif;

      // target
      $target = ($page->getCollectionPointerExternalLink() != '' && $page->openCollectionPointerExternalLinkInNewWindow()) ? '_blank' : $page->getAttribute('nav_target');
      $target = empty($target) ? '_self' : $target;
      $page->mclDetails['target'] = $target;
      $page->mclDetails['link'] = 'href="' . $page->mclDetails['url'] . '"' . ' target="' . $page->mclDetails['target'] . '"';
      $page->mclDetails['to'] = $page->mclDetails['link'] . ' class="' . $page->mclDetails['popupClassLauncher'] . '"';

      // title
      $title_text =  $th->entities($page->getCollectionName());
      $page->mclDetails['title'] = $controller->useButtonForLink ? $title_text : ('<a ' . $page->mclDetails['to'] . '>' . $title_text . '</a>') ;
      $page->mclDetails['name'] = $title_text;

      // date
      $eventDate = $page->getAttribute($options['alternativeDateAttributeHandle']);
      $page->mclDetails['date'] =  $eventDate ? $dh->formatDate($eventDate) : date('M / d / Y',strtotime($page->getCollectionDatePublic()));
      $page->mclDetails['rawdate'] =  $eventDate ? $dh->formatDate($eventDate) : strtotime($page->getCollectionDatePublic());

      // user
      if ($displayUser) $page->mclDetails['original_author'] = Page::getByID($page->getCollectionID(), 1)->getVersionObject()->getVersionAuthorUserName();

      // tags
      $tagsArray = $tagsObject->pageTags[$page->getCollectionID()];
      $page->mclDetails['tagsArray'] = $tagsObject->pageTagsName[$page->getCollectionID()] ? $tagsObject->pageTagsName[$page->getCollectionID()] : array();

      // topics
      if ($displaytopics) $page->mclDetails['topics'] = $page->getAttribute($options['topicAttributeKeyHandle']);

      // description
      if ($controller->includeDescription):
        $description = $page->getCollectionDescription();
        $description = $controller->truncateSummaries ? $th->wordSafeShortText($description, $controller->truncateChars) : $description;
        $page->mclDetails['description'] = $th->entities($description);
      endif;

      // Icon
      $page->mclDetails['icon'] = $page->getAttribute('icon') ? "<i class=\"fa {$page->getAttribute('icon')}\"></i>" : false;

      // Thumbnail
      if ($controller->displayThumbnail) :
        $img_att = $page->getAttribute('thumbnail');
        if (is_object($img_att)) :
          $img = Core::make('html/image', array($img_att, true));
          $page->mclDetails['imageTag'] = $img->getTag();
          $page->mclDetails['thumbnailUrl'] = ($type != NULL) ? $img_att->getThumbnailURL($type->getBaseVersion()) : false;
          $page->mclDetails['imageUrl'] = $img_att->getURL();
        else :
          $page->mclDetails['imageTag'] = $page->mclDetails['thumbnailUrl'] = false;
        endif;
      endif;

      // Item classes
      $itemClassesTemp = $itemClasses;
      $itemClassesTemp[] = $key % 2 == 1 ? 'pair' : 'impair';
      $itemClassesTemp[] = $tagsArray ? implode(' ',$tagsArray) : '';
      // Item tag
      $page->mclDetails['itemOpenTag'] = (($key%$styleObject->columns == 0 && $isStaticGrid) ? '<div class="row' . (in_array('no-gap',$styleObject->classesArray) ? ' no-gap' : '') . '">' : '') . '<' . $options['itemTag'] . ' class="' .implode(' ',  array_merge($itemClassesTemp,$options['additionalItemClasses'])) . '" ' . implode(' ', $itemAttributes) . '>' . ($options['AddInnerDiv'] ? '<div class="inner">' : '');
      $page->mclDetails['itemCloseTag'] = ($options['AddInnerDiv'] ? '</div>' : '') . '</' . $options['itemTag'] . '>' . (($key%$styleObject->columns == ($styleObject->columns) - 1 || ($key == count($pages)-1)) && $isStaticGrid ? '</div><!-- .row -->' : '');

    endforeach;
    if ($c->isEditMode() && $options['hideEditMode']) :
        echo '<div class="ccm-edit-mode-disabled-item">';
        echo '<p style="padding: 40px 0px 40px 0px;">' .
          '[ ' . $blockTypeHandle . ' ] ' .
          '<strong>' .
          ucwords($templateCleanName) .
          ($isCarousel ? t(' carousel') : '') .
          ($isMasonry ? t(' masonry') : '') .
          ($isStaticGrid ? t(' static grid') : '') .
          '</strong>' .
          t(' with ') .
          $styleObject->columns .
          t(' columns and ') .
          (!(in_array('no-gap',$styleObject->classesArray)) ? t(' regular Gap ') : t('no Gap ')) .
          t(' disabled in edit mode.') .
          '</p>';
        echo '</div>';
    endif;

    if ($controller->pageListTitle):
      echo '<div class="page-list-header">';
      echo '<h3>' . $controller->pageListTitle . '</h3>';
      echo '</div>';
    endif;

    if (!$c->isEditMode() && $isMasonry)
      Loader::PackageElement("page_list/sortable", 'theme_supermint', array('o'=>$o,'tagsObject'=>$tagsObject,'bID'=>$b->getBlockID(),'styleObject'=>$styleObject));

    return $vars;

  }
  function nl2p($string)  {
      $paragraphs = '';
      foreach (explode("\n", $string) as $line) if (trim($line)) $paragraphs .= '<p>' . $line . '</p>';
      return $paragraphs;
  }

  function contrast ($hexcolor, $dark = '#000000', $light = '#FFFFFF') {
      return (hexdec($hexcolor) > 0xffffff/2) ? $dark : $light;
  }


}
