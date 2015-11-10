<?php
namespace Concrete\Package\AddonPortfolio;

defined('C5_EXECUTE') or die('Access Denied.');
use \Concrete\Core\Block\BlockType\BlockType;

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Route;
use Events;
use Loader;

use Concrete\Package\AddonPortfolio\Src\Helper\MclInstaller;

class Controller extends \Concrete\Core\Package\Package {

    protected $pkgHandle = 'addon_portfolio';
    protected $appVersionRequired = '5.7.5';
    protected $pkgVersion = '0.1';
    protected $pkg;

    public function getPackageDescription() {
        return t("Add a sortable page-list to your portfolio");
    }

    public function getPackageName() {
        return t("Page-list portfolio");
    }

    public function on_start() {
        $this->registerAssets();
    }

    public function registerAssets()
    {
        $al = AssetList::getInstance();
        $al->register( 'css', 'easy-gallery-view', 'blocks/addon_portfolio/stylesheet/block-view.css', array('version' => '1'), $this );

        // View items
        $al->register( 'javascript', 'imagesloaded', 'blocks/addon_portfolio/javascript/build/imagesloaded.pkgd.min.js', array('version' => '3.1.4'), $this );
        $al->register( 'javascript', 'isotope', 'blocks/addon_portfolio/javascript/build/isotope.pkgd.min.js', array('version' => '3.1.4'), $this );
        $al->register( 'javascript', 'lazyload', 'blocks/addon_portfolio/javascript/build/jquery.lazyload.min.js', array('version' => '1.9.1'), $this );

    }

    public function install() {

    // Get the package object
        $this->pkg = parent::install();

    // Installing
        $this->installOrUpgrade();

    }


    private function installOrUpgrade() {
        $ci = new MclInstaller($this->pkg);
        $ci->importContentFile($this->getPackagePath() . '/config/install/base/blocktypes.xml');
        $ci->importContentFile($this->getPackagePath() . '/config/install/base/attributes.xml');
    }

    public function upgrade () {
        $this->pkg = $this;

        $this->installOrUpgrade();
        parent::upgrade();
    }

}
