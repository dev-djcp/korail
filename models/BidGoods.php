<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class BidGoods extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'bid_goods';
  }

  public static function getDb(){
    return Module::getInstance()->db;
  }

  public function rules(){
    return [
      [['bidid','seq'],'required'],
      [['gname','gorg','standard','unit','unitcost','period','place','condition'],'safe'],
    ];
  }

  public function afterFind(){
    parent::afterFind();
    if($this->gname)     $this->gname=    iconv('euckr','utf-8',$this->gname);
    if($this->gorg)      $this->gorg=     iconv('euckr','utf-8',$this->gorg);
    if($this->standard)  $this->standard= iconv('euckr','utf-8',$this->standard);
    if($this->unit)      $this->unit=     iconv('euckr','utf-8',$this->unit);
    if($this->unitcost)  $this->unitcost= iconv('euckr','utf-8',$this->unitcost);
    if($this->period)    $this->period=   iconv('euckr','utf-8',$this->period);
    if($this->place)     $this->place=    iconv('euckr','utf-8',$this->place);
    if($this->condition) $this->condition=iconv('euckr','utf-8',$this->condition);
  }

  public function beforeSave($insert){
    if(parent::beforeSave($insert)){
      if($this->gname)     $this->gname=    iconv('utf-8','euckr',$this->gname);
      if($this->gorg)      $this->gorg=     iconv('utf-8','euckr',$this->gorg);
      if($this->standard)  $this->standard= iconv('utf-8','euckr',$this->standard);
      if($this->unit)      $this->unit=     iconv('utf-8','euckr',$this->unit);
      if($this->unitcost)  $this->unitcost= iconv('utf-8','euckr',$this->unitcost);
      if($this->period)    $this->period=   iconv('utf-8','euckr',$this->period);
      if($this->place)     $this->place=    iconv('utf-8','euckr',$this->place);
      if($this->condition) $this->condition=iconv('utf-8','euckr',$this->condition);
      return true;
    }
    return false;
  }
}

