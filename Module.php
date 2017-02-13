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

  public $gman_server;
  protected $gman_client;
  protected $talk_client;

  public function init(){
    parent::init();

    $this->db=Instance::ensure($this->db,Connection::className());
  }

  public function gman_do($func,$data){
    if($this->gman_client===null){
      $this->gman_client=new \GearmanClient;
      $this->gman_client->addServers($this->gman_server);
    }
    if(is_array($data)) $data=Json::encode($data);
    $this->gman_client->doNormal($func,$data);
  }

  public function gman_doBack($func,$data){
    if($this->gman_client===null){
      $this->gman_client=new \GearmanClient;
      $this->gman_client->addServers($this->gman_server);
    }
    if(is_array($data)) $data=Json::encode($data);
    $this->gman_client->doBackground($func,$data);
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

