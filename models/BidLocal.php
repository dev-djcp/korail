<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class BidLocal extends \yii\db\ActiveRecord
{
	public static function tableName(){
		return 'bid_local';
	}

	public static function getDb(){
		return Module::getInstance()->db;
	}

	public function rules(){
		return [
			[['bidid','code'],'required'],
			[['name'],'safe'],
		];
	}

	public function afterFind(){
		parent::afterFind();
		if($this->name)			$this->name =		iconv('euckr','utf-8',$this->name);
	}

	public function beforeSave($insert){
		if(parent::beforeSave($insert)){
			if($this->name)			$this->name =		iconv('utf-8','euckr',$this->name);
			return true;
		}
		return false;
	}
}