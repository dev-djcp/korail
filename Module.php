<?php
namespace djcp\korail;

use Yii;
use yii\base\BootstrapInterface;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\Json;

class Module extends \yii\base\Module implements BootstrapInterface
{
  public $db='db';

  public function init(){
    parent::init();

    $this->db=Instance::ensure($this->db,Connection::className());
  }

  public function bootstrap($app){
    if($app instanceof \yii\console\Application){
      $app->controllerMap[$this->id]=[
        'class'=>'djcp\korail\console\CrawlerController',
        'crawlers'=>$this->coreCrawlers(),
        'module'=>$this,
      ];
    }
  }

  protected function coreCrawlers(){
    return [
      'bid'=>['class'=>'djcp\korail\crawlers\bid\Crawler'],
      'suc'=>['class'=>'djcp\korail\crawlers\suc\Crawler'],
    ];
  }
}

