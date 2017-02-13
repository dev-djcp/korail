<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class BidContent extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'bid_content';
  }

  public static function getDb(){
    return Module::getInstance()->db;
  }

  public function rules(){
    return [
      [['bidid'],'required'],
      [['bidcomment_mod','bidcomment','nbidcomment'],'safe'],
      [['orign_lnk','s_orign_lnk','attchd_lnk','pur_lnk'],'safe'],
      [['bid_html','nbid_html','bid_file','nbid_file','pur_goods'],'safe'],
    ];
  }
  
  public function beforeSave($insert){
    if(parent::beforeSave($insert)){
      if($this->bidcomment_mod) $this->bidcomment_mod=iconv('utf-8','euckr//IGNORE',$this->bidcomment_mod);
      if($this->bidcomment)     $this->bidcomment=    iconv('utf-8','euckr//IGNORE',$this->bidcomment);
      if($this->bid_html)       $this->bid_html=      iconv('utf-8','euckr//IGNORE',$this->bid_html);
      if($this->nbidcomment)    $this->nbidcomment=   iconv('utf-8','euckr//IGNORE',$this->nbidcomment);
      if($this->nbid_html)      $this->nbid_html=     iconv('utf-8','euckr//IGNORE',$this->nbid_html);
      if($this->bid_file)       $this->bid_file=      iconv('utf-8','euckr//IGNORE',$this->bid_file);
      if($this->nbid_file)      $this->nbid_file=     iconv('utf-8','euckr//IGNORE',$this->nbid_file);
      if($this->attchd_lnk)     $this->attchd_lnk=    iconv('utf-8','euckr//IGNORE',$this->attchd_lnk);
      return true;
    }
    return false;
  }
  
  public function afterFind(){
    parent::afterFind();
    if($this->bidcomment_mod) $this->bidcomment_mod=iconv('euckr','utf-8//IGNORE',$this->bidcomment_mod);
    if($this->bidcomment)     $this->bidcomment=    iconv('euckr','utf-8//IGNORE',$this->bidcomment);
    if($this->bid_html)       $this->bid_html=      iconv('euckr','utf-8//IGNORE',$this->bid_html);
    if($this->nbidcomment)    $this->nbidcomment=   iconv('euckr','utf-8//IGNORE',$this->nbidcomment);
    if($this->nbid_html)      $this->nbid_html=     iconv('euckr','utf-8//IGNORE',$this->nbid_html);
    if($this->bid_file)       $this->bid_file=      iconv('euckr','utf-8//IGNORE',$this->bid_file);
    if($this->nbid_file)      $this->nbid_file=     iconv('euckr','utf-8//IGNORE',$this->nbid_file);
    if($this->attchd_lnk)     $this->attchd_lnk=    iconv('euckr','utf-8//IGNORE',$this->attchd_lnk);
  }
}

