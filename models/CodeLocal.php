<?php
namespace djcp\korail\models;

use djcp\korail\Module;

class CodeLocal extends \yii\db\ActiveRecord
{
	public static function tableName(){
		return 'code_local';
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
		if($this->name)	$this->name=iconv('euckr','utf-8',$this->name);
	}

	public function beforeSave($insert){
		return false;
	}

	public static function findByName($name){
		$name=iconv('utf-8','euckr',$name);
		return static::findOne(['name'=>$name]);
	}
}






