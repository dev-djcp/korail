<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class CodeOrgI extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'code_org_i';
  }

  public static function getDb(){
    return Module::getInstance()->db;
  }

  public function rules(){
    return [
    ];
  }

  public function afterFind(){
    parent::afterFind();
    if($this->org_name) $this->org_name=iconv('euckr','utf-8',$this->org_name);
    if($this->result_name) $this->result_name=iconv('euckr','utf-8',$this->result_name);
  }

  public function beforeSave($insert){
    return false;
  }

  public static function findByOrgname($org_name){
    $org_name=iconv('utf-8','euckr',$org_name);
    return static::findOne(['org_name'=>$org_name]);
  }
}

