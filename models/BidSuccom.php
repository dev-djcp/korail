<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class BidSuccom extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'bid_succom';
  }

  public static function getDb(){
    return Module::getInstance()->db;
  }

  public static function primaryKey(){
    return ['bidid','seq'];
  }

  public function rules(){
    return [
    ];
  }

  public function beforeSave($insert){
    if(parent::beforeSave($insert)){
      if($this->officenm) $this->officenm=iconv('utf-8','euckr',$this->officenm);
      if($this->prenm) $this->prenm=iconv('utf-8','euckr',$this->prenm);
      if($this->etc) $this->etc=iconv('utf-8','euckr',$this->etc);
      return true;
    }
    return false;
  }

  public function afterFind(){
    parent::afterFind();
    if($this->officenm) $this->officenm=iconv('euckr','utf-8',$this->officenm);
    if($this->prenm) $this->prenm=iconv('euckr','utf-8',$this->prenm);
    if($this->etc) $this->etc=iconv('euckr','utf-8',$this->etc);
  }
}

