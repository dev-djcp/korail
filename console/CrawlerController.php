<?php
namespace djcp\korail\console;

use Yii;
use yii\base\InlineAction;

class CrawlerController extends \yii\console\Controller
{  
  public $crawlers=[];
  private $_options=[];

  public function __get($name){
    return isset($this->_options[$name]) ? $this->_options[$name] : null;
  }

  public function __set($name,$value){
    $this->_options[$name]=$value;
  }

  public function init(){
    parent::init();
    foreach($this->crawlers as $id=>$config){
      $this->crawlers[$id]=Yii::createObject($config);
    }
  }

  public function createAction($id){
    $action=parent::createAction($id);
    foreach($this->_options as $name=>$value){
      $action->crawler->$name=$value;
    }
    return $action;
  }

  public function actions(){
    $actions=[];
    foreach($this->crawlers as $name=>$crawler){
      $actions[$name]=[
        'class'=>'djcp\korail\console\CrawlerAction',
        'crawler'=>$crawler,
      ];
    }
    return $actions;
  }

  public function options($id){
    $options=parent::options($id);

    if(!isset($this->crawlers[$id])){
      return $options;
    }

    $attributes=$this->crawlers[$id]->attributes;
    return array_merge($options,array_keys($attributes));
  }

  public function actionIndex(){
    $this->run('/help',['korail']);
  }

  public function getUniqueID(){
    return $this->id;
  }

  public function getActionHelpSummary($action){
    if($action instanceof InlineAction){
      return parent::getActionHelpSummary($action);
    }
    else if($action instanceof CrawlerAction){
      return $action->crawler->getName();
    }
  }

  public function getActionHelp($action){
    if($action instanceof InlineAction){
      return parent::getActionHelp($action);
    }else{
      if($action instanceof CrawlerAction){
        $description=$action->crawler->getDescription();
      }
      return wordwrap(preg_replace('/\s+/',' ',$description));
    }
  }

  public function getActionArgsHelp($action){
    return [];
  }

  public function getActionOptionsHelp($action){
    if($action instanceof InlineAction){
      return parent::getActionOptionsHelp($action);
    }

    $attributes=$action->crawler->attributes;
    $hints=$action->crawler->hints();

    $options=[];
    foreach($attributes as $name=>$value){
      $type=gettype($value);
      $options[$name]=[
        'type'=>$type==='NULL' ? 'string' : $type,
        'required'=>$value===NULL && $action->crawler->isAttributeRequired($name),
        'default'=>$value,
        'comment'=>isset($hints[$name]) ? $this->formatHint($hints[$name]) : '',
      ];
    }

    return $options;
  }

  protected function formatHint($hint){
    $hint=preg_replace('%<code>(.*?)</code>%','\1',$hint);
    $hint=preg_replace('/\s+/',' ',$hint);
    return wordwrap($hint);
  }
}

