<?php
defined('C5_EXECUTE') or die("Access Denied.");

	$c = Page::getCurrentPage();
	$helper = new Concrete\Package\AddonPortfolio\Controller\AddonPortfolioHelper;

  $ag = \Concrete\Core\Http\ResponseAssetGroup::get();
  $ag->requireAsset('javascript','imagesloaded');
	$ag->requireAsset('javascript','isotope');
	$ag->requireAsset('javascript','lazyload');

	$rssUrl = $showRss ? $controller->getRssUrl($b) : '';
	$th = Loader::helper('text');
	$dh = Core::make('helper/date');
	$tagsObject = $helper->getPageTags($pages);

	if ($includeName || $includeDescription || $useButtonForLink) $includeEntryText = true; else $includeEntryText = false;
	$styleObject = $helper->getClassSettingsObject($b);
	$column_class = $styleObject->columns > 3 ? 'col-md-' : 'col-sm-';
	$gap = !(in_array('no-gap',$styleObject->classesArray));
	$type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($styleObject->columns > 1 ? 'medium' : 'large');
	$topicAttributeKeyHandle = "project_topics";
	$tagAttributeHandle = "tags";

	if ($c->isEditMode()) : ?>
		<?php $templateName = $controller->getBlockObject()->getBlockFilename() ?>
	    <div class="ccm-edit-mode-disabled-item" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>">
					<p style="padding: 40px 0px 40px 0px;"><strong><?php echo  ucwords(str_replace('_', ' ', substr( $templateName, 0, strlen( $templateName ) -4 ))) . '</strong>' . t(' with ') .  $styleObject->columns . t(' columns and ') . ($gap ? t(' regular Gap ') : t('no Gap ')) . t(' disabled in edit mode.') ?></p>
	    </div>
	<?php else :?>

<?php Loader::PackageElement("page_list/sortable", 'addon_portfolio', array('o'=>$o,'tagsObject'=>$tagsObject,'bID'=>$bID,'styleObject'=>$styleObject))?>
<?php  if ($pageListTitle): ?><div class="page-list-header"><h3><?php  echo $pageListTitle?></h3></div><?php  endif?>
<div class="portfolio-gallery page-list-masonry row <?php echo $gap ? 'with-gap' : 'no-gap' ?>" data-gridsizer=".<?php echo $column_class . intval(12 / $styleObject->columns)?>" data-bid="<?php echo $bID?>">
	<?php  foreach ($pages as $key => $page):

		$externalLink = $page->getAttribute('external_link');
		$url = $externalLink ? $externalLink : $nh->getLinkToCollection($page);

		if ($page->getPageTemplateHandle() == 'popup_content'):
			$url = "#portfolio-popup-{$page->getCollectionID()}";
			$view = $page->getController()->getViewObject();
			$page->isPopup = true;
		endif;

		$title_text =  $th->entities($page->getCollectionName());
		$title = $useButtonForLink ? "<a href=\"$url\" target=\"$target\"" . ($page->isPopup ? 'class="open-popup-link"' : '') . ">$title_text</a>" : $title_text;
		$tags = isset($tagsObject->pageTags[$page->getCollectionID()]) ? implode(' ',$tagsObject->pageTags[$page->getCollectionID()]) : '';

    $date = date('M / d / Y',strtotime($page->getCollectionDatePublic()));

		$target = ($page->getCollectionPointerExternalLink() != '' && $page->openCollectionPointerExternalLinkInNewWindow()) ? '_blank' : $page->getAttribute('nav_target');
		$target = empty($target) ? '_self' : $target;
    $original_author = Page::getByID($page->getCollectionID(), 1)->getVersionObject()->getVersionAuthorUserName();

		if ($includeDescription):
			$description = $page->getCollectionDescription();
			$description = $controller->truncateSummaries ? $th->wordSafeShortText($description, $controller->truncateChars) : $description;
			$description = $th->entities($description);
		endif;
		if ($displayThumbnail) :
      $img_att = $page->getAttribute('thumbnail');
      if (is_object($img_att)) :
      	$img = Core::make('html/image', array($img_att, true));
      	$imageTag = $img->getTag();
      endif;
    endif;
?>
<div class="<?php echo $column_class . intval(12 / $styleObject->columns)?> item masonry-item <?php echo $tags ?>">
	<?php if (!$useButtonForLink): ?><a href="<?php echo $url ?>" target="<?php echo $target ?>" class="open-popup-link"><?php endif ?>
	<?php if ($imageTag) : echo $imageTag; else :?><img src="data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg' viewBox%3D'0 0 16 9'%2F%3E" width="640" height="360" class="placeholder" /><?php endif ?>
	<?php if ($includeEntryText): ?>
	<div class="info">
		<div class="vertical-align">
			<?php if ($includeDate): ?>
				<div class="meta">
					<small><i class="fa fa-calendar-o"></i> <?php echo $date?></small>
					<?php if($o->carousel_meta) : ?><small> <i class="fa fa-user"></i> <?php echo $original_author ?></small><?php endif ?>
				</div>
			<?php endif; ?>
			<?php if ($includeName): ?><h4><?php echo $title ?></h4><?php endif ?>
			<?php if ($includeDescription): ?><p><?php  echo $description ?></p><?php endif ?>
			<?php if ($useButtonForLink): ?><a href="<?php echo $url?>" class="button-primary button-flat button-tiny <?php echo $page->isPopup ? 'open-popup-link' : '' ?>"><?php echo $buttonLinkText?></a><?php endif ?>
		</div>
	</div>
	<?php endif ?>
	<?php if (!$useButtonForLink): ?></a><?php endif ?>
	<?php if ($page->isPopup): ?>
	<div class='white-popup mfp-hide large-popup' id="portfolio-popup-<?php echo $page->getCollectionID()?>">		<div class="popup-scroll">
	<?php if (!$c->isEditMode()) :
		Request::getInstance()->setCurrentPage($page);
		echo $view->render("popup_content");
		Request::getInstance()->setCurrentPage($c);
	endif;
	?></div></div>
	<?php endif ?>
	</div>
	<?php  endforeach; ?>

    <?php  if (count($pages) == 0): ?>
        <div class="ccm-block-page-list-no-pages"><?php  echo $noResultsMessage?></div>
    <?php  endif;?>


	<?php  if ($showRss): ?>
		<div class="ccm-block-page-list-rss-icon">
			<a href="<?php  echo $rssUrl ?>" target="_blank"><img src="<?php  echo $rssIconSrc ?>" width="14" height="14" alt="<?php  echo t('RSS Icon') ?>" title="<?php  echo t('RSS Feed') ?>" /></a>
		</div>
		<link href="<?php  echo BASE_URL.$rssUrl ?>" rel="alternate" type="application/rss+xml" title="<?php  echo $rssTitle; ?>" />
	<?php  endif; ?>

</div><!-- end .ccm-block-page-list -->


<?php  if ($showPagination): ?>
    <?php  echo $pagination;?>
<?php  endif; ?>
<?php  endif; ?>
