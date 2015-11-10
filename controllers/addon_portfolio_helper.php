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
                $tagsObject->tags[$handle] = $opt['value'];
            }
        endif ;
    endforeach;
    return $tagsObject;
  }

  function setBlock ($b) {
    $new = false;
    if (!is_object($this->block)) {
      $this->block = $b;
      $new = true;
    }
    if ($b->getBlockTypeHandle() != $this->block->getBlockTypeHandle() ||
        $b->getBlockID() != $this->block->getBlockID() ||
        $new
       ):
      $style = $b->getCustomStyle();
      $this->cc = (is_object($b) && is_object($style)) ? $style->getStyleSet()->getCustomClass() : '';
      $this->cs =  is_object($style) ? $style : false;
    endif;
  }

  function getClassSettingsString ($b) {
    $this->setBlock ($b);
    return $this->cc;
  }

  function getClassSettingsArray ($b) {
    $this->setBlock ($b);
    return explode(' ',  $this->cc);
  }

  function getClassSettingsPrefixInt ($b,$prefix,$string = false) {
    $this->setBlock ($b);
    $_string = $tring ? $string : $this->cc;
    preg_match('/' . $prefix . '-(\w+)/',$_string,$found);
    return isset($found[1]) ? (int)$found[1] : false;
  }

  ## return words AFTER $prefix (element-)primary
  function getClassSettingsPrefixString ($b,$prefix,$string = false) {
    $this->setBlock ($b);
    $_string = $tring ? $string : $this->cc;
    preg_match('/' . $prefix . '-(\w+)/',$_string,$found);
    return isset($found[1]) ? $found[1] : false;
  }

  function getCustomStyleImage ($b) {
    $this->setBlock ($b);
    if ($this->cs) {
        $set = $this->cs->getStyleSet();
        $image = $set->getBackgroundImageFileObject();
        if (is_object($image)) {
            return $image;
        }
    }
    return false;
  }

  function getClassSettingsObject ($block, $defaultColumns = 3, $defaultMargin = 10  ) {
    $this->setBlock ($block);
    $styleObject = new StdClass();

    if ($this->cs) :
      // We get string as 'first-class second-class'
      $classes = $this->cc;
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

  function contrast ($hexcolor, $dark = '#000000', $light = '#FFFFFF') {
      return (hexdec($hexcolor) > 0xffffff/2) ? $dark : $light;
  }

}
