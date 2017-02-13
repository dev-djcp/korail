<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class BidKey extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'bid_key';
  }

  public static function getDb(){
    return Module::getInstance()->db;
  }
  
  public function rules(){
    return [
      [['notinum_ex'],'default','value'=>''],
      [['bidproc'],'default','value'=>'B'],
      [['opt'],'default','value'=>0],
      [['bidcls'],'default','value'=>'01'],
      [['contract'],'default','value'=>'10'],
      [['succls'],'default','value'=>'00'],
      [['bidid'],'required'],
      [['whereis','syscode','bidtype','bidview','notinum','notinum_ex'],'safe'],
      [['constnm','org','org_i','orgcode_i','conlevel','concode','sercode','purcode'],'safe'],
      [['location','convention','presum','basic','pct'],'safe'],
      [['noticedt','registdt','explaindt','agreedt','opendt','closedt','constdt','writedt','pqdt','docdt','editdt'],'safe'],
      [['inputer','inspecter','state','no','lock','isclosed'],'safe'],
    ];
  }
  
  public function beforeSave($insert){
    if(parent::beforeSave($insert)){
      if($this->notinum) $this->notinum=iconv('utf-8','euckr',$this->notinum);
      if($this->constnm) $this->constnm=iconv('utf-8','euckr',$this->constnm);
      if($this->org_i) $this->org_i=iconv('utf-8','euckr',$this->org_i);
      return true;
    }
    return false;
  }
  
  public function afterFind(){
    parent::afterFind();
    if($this->notinum) $this->notinum=iconv('euckr','utf-8',$this->notinum);
    if($this->constnm) $this->constnm=iconv('euckr','utf-8',$this->constnm);
    if($this->org_i) $this->org_i=iconv('euckr','utf-8',$this->org_i);
  }

  public function getBidValue(){
    return $this->hasOne(BidValue::className(),['bidid'=>'bidid']);
  }

  public function getBidContent(){
    return $this->hasOne(BidContent::className(),['bidid'=>'bidid']);
  }

  public function getBidGoods(){
    return $this->hasMany(BidGoods::className(),['bidid'=>'bidid']);
  }

  public function getBidSubcode(){
    return $this->hasMany(BidSubcode::className(),['bidid'=>'bidid']);
  }

	public function getBidLocal(){
		return $this->hasMany(BidLocal::className(),['bidid'=>'bidid']);
	}
}

