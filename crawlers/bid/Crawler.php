<?php
namespace djcp\korail\crawlers\bid;

use yii\helpers\Json;

class Crawler extends \djcp\korail\Crawler
{
  public $date1;
  public $date2;
  public $notinum;
  public $revision;

  public function getName(){
    return '코레일 입찰정보';
  }

  public function getDescription(){
    return '코레일 입찰정보를 수집합니다.';
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
    switch($this->bidtype){
    case 'ser':
      $rows=$this->searchSer();
      break;
    case 'pur':
      $list1=$this->searchPur1();
      $list2=$this->searchPur2();
      $rows=array_merge($list1,$list2);
      break;
    default:
      $list1=$this->searchSer();
      $list2=$this->searchPur1();
      $list3=$this->searchPur2();
      $rows=array_merge($list1,$list2,$list3);
    }
    $answer=$callback($rows);
    switch($answer){
    case 'n': return;
    case 'y':
      // 미입력 공고만 처리
      // 상세 가져오기
      break;
    case 'a':
      // 모두 처리
      // 상세가져오기
      foreach($rows as $row){
        $p=[
          ['name'=>'I_ZBIDINV','value_string'=>$row['notinum']],
          ['name'=>'I_ZESTNUMM','value_string'=>$row['revision']],
        ];
        $fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0010'];
        $detail=$this->getDetail($p,$fn);
        //입력처리
        //
        print_r($detail);

        sleep(1);
      }
      break;
    }
  }

  public function watch($callback){
    while(true){
      $list1=$this->searchSer();
      $list2=$this->searchPur1();
      $list3=$this->searchPur2();
      $rows=array_merge($list1,$list2,$list3);
      foreach($rows as $row){
        $p=[
          ['name'=>'I_ZBIDINV','value_string'=>$this->notinum],
          ['name'=>'I_ZESTNUMM','value_string'=>$this->revision],
        ];
        $fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0010'];
        // 상세가져오기

        $callback($row);
        sleep(1);
      }
      sleep(5);
    }
  }

  public function detail($callback){
    $p=[
      ['name'=>'I_ZBIDINV','value_string'=>$this->notinum],
      ['name'=>'I_ZESTNUMM','value_string'=>$this->revision],
    ];
    $fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0010'];

    $data=$this->getDetail($p,$fn);
    $answer=$callback($data);
    //todo: 입력처리
  }

  public function getDetail(array $p,array $fn){
    $p=Json::encode($p);
    $fn=Json::encode($fn);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
    $itab=$result['itab'];

    $bidinfo=[]; //입찰정보
      
    foreach($itab as $row){
      if($row['name']=='ET_DOCLIST'){
        $bidinfo['files']=$row['value'];
      }else if($row['name']=='ET_ZSMMEEBID0004'){
        $d=$row['value'][0];
        $bidinfo['constnm']=$d['description'];
        $bidinfo['bidtype']=($d['zzbidtypecode']=='5')?'ser':'pur'; //물품(내자)=1,물품(외자)=9
        //
        //
        //
        //
      }
    }

    return $bidinfo;
  }

  private function _listToNormal($a){
    $b['notinum']=$a['zzbidinv'];
    $b['revision']=$a['zzstnum'];
    $b['constnm']=$a['description'];
    return $b;
  }

  /**
   * 용역
   */
  public function searchSer(){
    $p=[];
    $p[]=['name'=>'I_ZZBIDTYPECODE','value_string'=>'5'];
    $p[]=['name'=>'I_ERDAT_FR','value_string'=>$this->date1];
    $p[]=['name'=>'I_ERDAT_TO','value_string'=>$this->date2];
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0009']);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
    $itab=$result['itab'];
    foreach($itab as $row){
      if($row['kind']==30){
        $data=[];
        foreach($row['value'] as $r){
          $a=$this->_listToNormal($r);
          $a['bidtype']='ser';
          $data[]=$a;
        }
        return $data;
      }
    }
    return [];
  }

  /**
   * 물품(내자)
   */
  public function searchPur1(){
    $p=[];
    $p[]=['name'=>'I_ZZBIDTYPECODE','value_string'=>'1'];
    $p[]=['name'=>'I_ERDAT_FR','value_string'=>$this->date1];
    $p[]=['name'=>'I_ERDAT_TO','value_string'=>$this->date2];
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0009']);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
    $itab=$result['itab'];
    foreach($itab as $row){
      if($row['kind']==30){
        $data=[];
        foreach($row['value'] as $r){
          $a=$this->_listToNormal($r);
          $a['bidtype']='pur';
          $data[]=$a;
        }
        return $data;
      }
    }
    return [];
  }

  /**
   * 물품(외자)
   */
  public function searchPur2(){
    $p=[];
    $p[]=['name'=>'I_ZZBIDTYPECODE','value_string'=>'9'];
    $p[]=['name'=>'I_ERDAT_FR','value_string'=>$this->date1];
    $p[]=['name'=>'I_ERDAT_TO','value_string'=>$this->date2];
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0009']);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
    $itab=$result['itab'];
    foreach($itab as $row){
      if($row['kind']==30){
        $data=[];
        foreach($row['value'] as $r){
          $a=$this->_listToNormal($r);
          $a['bidtype']='pur';
          $data[]=$a;
        }
        return $data;
      }
    }
    return [];
  }
}

