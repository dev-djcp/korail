<?php
namespace djcp\korail\crawlers\suc;

class Crawler extends \djcp\korail\Crawler
{
  public $date1;
  public $date2;
  public $notinum;
  public $revision;

  public function getName(){
    return '코레일 낙찰정보';
  }

  public function getDescription(){
    return '코레일 낙찰정보를 수집합니다.';
  }

  public function rules(){
    return array_merge(parent::rules(),[
      [['date1','date2'],'default','value'=>function($model,$attribute){
        return date('Ymd',strtotime($attribute==='date1' ? '-1 month' : ''));
      },'on'=>'watch'],
      [['date1','date2'],'date','format'=>'php:Ymd'],
      [['date1','date2'],'required','on'=>'search'],
      [['notinum','revision'],'required','on'=>'detail'],
    ]);
  }

  public function attributeLabels(){
    return array_merge(parent::attributeLabels(),[
      'date1'=>'공고일 시작일',
      'date2'=>'공고일 종료일',
      'notinum'=>'공고번호',
      'revision'=>'차수',
    ]);
  }

  public function hints(){
    return array_merge(parent::hints(),[
      'date1'=>'검색옵션: 공고일 시작일 (default: -1 month), e.g., <code>20170107</code>',
      'date2'=>'검색옵션: 공고일 종료일 (default: today), e.g., <code>20170207</code>',
      'notinum'=>'검색 및 수집옵션: 공고번호, e.g., <code>9200126</code>',
      'revision'=>'수집옵션: 차수',
    ]);
  }

  public function search($callback){
  }

  public function watch($callback){
  }

  public function detail($callback){
  }
}
