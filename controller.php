<?php
namespace Concrete\Package\AddonPortfolio;

defined('C5_EXECUTE') or die('Access Denied.');
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
        return t("Add sortable templates to page-list");
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
        $al->register( 'javascript', 'imagesloaded', 'js/imagesloaded.pkgd.min.js', array('version' => '3.1.4'), $this );
        $al->register( 'javascript', 'isotope', 'js/isotope.pkgd.min.js', array('version' => '3.1.4'), $this );
        $al->register( 'javascript', 'lazyload', 'js/jquery.lazyload.min.js', array('version' => '1.9.1'), $this );
    }

    public function install() {

    // Get the package object
        $this->pkg = parent::install();

    // Installing
        $this->installOrUpgrade();
    }


    private function installOrUpgrade() {
        $ci = new MclInstaller($this->pkg);
        $ci->importContentFile($this->getPackagePath() . '/config/install/base/attributes.xml');
    }

    public function upgrade () {
        $this->pkg = $this;

        $this->installOrUpgrade();
        parent::upgrade();
    }

}
