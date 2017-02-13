<?php
namespace djcp\korail;

use yii\helpers\Json;

abstract class Crawler extends \yii\base\Model
{
  public $debug=false;
  public $proxy;
  public $mode='search';
  public $bidtype='all';
	public $module;

  protected $http;
  protected $base_uri='http://ebidn.korail.com:50000';

  public function init(){
    parent::init();

		$this->module=\djcp\korail\Module::getInstance();

    $this->http=new \GuzzleHttp\Client([
      'base_uri'=>$this->base_uri,
      'cookies'=>true,
      'allow_redirects'=>false,
      'headers'=>[
        'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Connection'=>'Keep-Alive',
        'Pragma'=>'no-cache',
        'Cache-Control'=>'no-cache',
        'Content-Type'=>'application/json',
      ],
    ]);

    $this->scenario=$this->mode;
  }

  abstract function getName();

  public function getDescription(){
    return '';
  }

  public function rules(){
    return [
      ['mode','in','range'=>['search','watch','detail']],
      ['bidtype','in','range'=>['all','ser','pur']],
    ];
  }

  public function hints(){
    return [
      'mode'=>'실행모드: search(공고검색), watch(공고감시), detail(공고상세수집)',
      'proxy'=>'Proxy server',
      'bidtype'=>'all: 모두, ser: 용역, pur: 물품',
    ];
  }

  public function request($method,$uri='',array $options=[]){
    if($this->debug){
      $options['debug']=true;
    }

    if($this->proxy){
      $options['proxy']['http']=$this->proxy;
    }

    $res=$this->http->request($method,$uri,$options);
    $body=$res->getBody();
    return $body;
  }

  public function get($uri,array $query=[]){
    $body=$this->request('GET',$uri,['query'=>$query]);
    return Json::decode($body);
  }
}

