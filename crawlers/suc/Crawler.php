<?php
namespace djcp\korail\crawlers\suc;

use yii\helpers\Json;
use djcp\korail\models\BidKey;

class Crawler extends \djcp\korail\Crawler
{
  public $date1;
  public $date2;
  public $notinum;
  public $revision;

	public $module;
	private $i2_func='i2_auto_suc';

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
    switch($this->bidtype){
    case 'ser':
      $rows=$this->searchSer();
      break;
    case 'pur':
      $list1=$this->searchPur1();
      //$list2=$this->searchPur2();
      $rows=array_merge($list1/*,$list2*/);
      break;
    default:
      $list1=$this->searchSer();
      $list2=$this->searchPur1();
      //$list3=$this->searchPur2();
      $rows=array_merge($list1,$list2/*,$list3*/);
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
					['name'=>'I_ZZBIDINV','value_string'=>$row['notinum']],
					['name'=>'I_ZZSTNUM','value_string'=>$row['revision']],
				];
				$fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0016'];

				$data=$this->getDetail($p,$fn);
				
				$this->suc_work($data);	        
        sleep(60);
      }
      break;
    }
  }

  public function watch($callback){
    while(true){
      $list1=$this->searchSer();
      $list2=$this->searchPur1();
      //$list3=$this->searchPur2();
      $rows=array_merge($list1,$list2/*,$list3*/);
      foreach($rows as $row){
				$p=[
					['name'=>'I_ZZBIDINV','value_string'=>$row['notinum']],
					['name'=>'I_ZZSTNUM','value_string'=>$row['revision']],
				];
				$fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0016'];

				// 상세가져오기
				$data=$this->getDetail($p,$fn);

				$this->suc_work($data);				

        $callback($row);
        sleep(60);
      }
      sleep(400);
    }
  }

  public function detail($callback){
  	$p=[
			['name'=>'I_ZZBIDINV','value_string'=>$this->notinum],
			['name'=>'I_ZZSTNUM','value_string'=>$this->revision],
  	];
  	$fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0016'];
  	
  	$data=$this->getDetail($p,$fn);

    $answer=$callback($data);
		switch($answer) {
    case 'n': return;
    case 'y':
			$this->suc_work($data);
			break;
		}
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
    if($this->mode=='search') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>$this->date1];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>$this->date2];			
			//$p[]=['name'=>'I_ERDAT_FR','value_string'=>$this->date1];
			//$p[]=['name'=>'I_ERDAT_TO','value_string'=>$this->date2];
		}else if($this->mode=='watch') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>date('Ymd',strtotime('-21 day'))];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>date('Ymd')];			
			//$p[]=['name'=>'I_ERDAT_FR','value_string'=>date('Ymd',strtotime('-30 day'))];
			//$p[]=['name'=>'I_ERDAT_TO','value_string'=>date('Ymd')];
		}
			
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0012']);
    //$result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
		$result=$this->post('/comnRfc.do',['form_params'=>['p'=>$p,'fn'=>$fn]]);		
		$data=Json::decode($result['data']);
		$itab=$data['itab'];
    
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
    if($this->mode=='search') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>$this->date1];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>$this->date2];
		}else if($this->mode=='watch') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>date('Ymd',strtotime('-21 day'))];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>date('Ymd')];
		}
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0012']);
    //$result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
		$result=$this->post('/comnRfc.do',['form_params'=>['p'=>$p,'fn'=>$fn]]);		
		$data=Json::decode($result['data']);
		$itab=$data['itab'];
    
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
    if($this->mode=='search') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>$this->date1];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>$this->date2];
		}else if($this->mode=='watch') {
			$p[]=['name'=>'I_SDAT_FR','value_string'=>date('Ymd',strtotime('-21 day'))];
			$p[]=['name'=>'I_SDAT_TO','value_string'=>date('Ymd')];
		}
    if($this->notinum) $p[]=['name'=>'I_ZZBIDINV','value_string'=>$this->notinum];
    $p=Json::encode($p);
    $fn=Json::encode(['ZFUNCNM'=>'ZMME_EBID_INFO_0012']);
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
	
  public function getDetail(array $p,array $fn) {
  	$p=Json::encode($p);
  	$fn=Json::encode($fn);
  	//$result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);
		$result=$this->post('/comnRfc.do',['form_params'=>['p'=>$p,'fn'=>$fn]]);		
		$data=Json::decode($result['data']);
		$itab=$data['itab'];  	
  	
  	$sucinfo=[];	//낙찰정보
  	
  	foreach($itab as $row) {
  		if($row['name']=='ET_ZSMMEEBID0012') {
  			$d=$row['value'][0];
				
				$sucinfo['s_orign_lnk']	='http://ebid.korail.com/goods/printOpen.do?zzbidinv='.$d['zzbidinv'].'&zzstnum='.$d['zzstnum'];
				$sucinfo['notinum']			=$d['zzbidinv'];
				$sucinfo['notinum_ex']	=intval($d['zzstnum'])>0?$d['zzstnum']:'';
				$sucinfo['constnm']			=$d['description'];
				$sucinfo['reswdt']			=date('Y-m-d H:i:s',strtotime($d['zzopen_dat'].' '.$d['zzopen_tim']));			//개찰등록일시
				//개찰결과(E-낙찰업체상신,Z-개찰 확정,N-적격심사중,R-재입찰처리,X-수의시담유찰처리,F-유찰 처리,Q-수의시담처리)
				if($d['openresult']=='E' or $d['openresult']=='Z' or $d['openresult']=='N') {
					$sucinfo['bidproc']	='S';
					$sucinfo['yega']				=$d['confirm_fcast'];
				}else if($d['openresult']=='R' or $d['openresult']=='X' or $d['openresult']=='F') {
					$sucinfo['bidproc']	='F';
					$sucinfo['officenm1']='유찰';
				}else if($d['openresult']=='Q') {
					$sucinfo['bidproc']='F';
					$sucinfo['officenm1']='유찰';
					$sucinfo['nbidcomment']='단독유찰,수의시담처리';
				}
  		}
			if($sucinfo['bidproc']=='S') {
				if($row['name']=='ET_ZSMMEEBID0014') {
					$succomList				=$row['value'];
					$plus=[];
					$minus=[];
					foreach($succomList as $i=>$row) {
						$seq=$i+1;
						$succom=[
							'seq'=>$seq,
							//'rank'=>$row['num'],
							'officeno'=>$row['stcd2'],
							'officenm'=>$row['name1'],
							'prenm'=>$row['j_1kfrepre'],
							'success'=>$row['zzvalue'],
							'pct'=>$row['zzvalue_rate'],
							'selms'=>$row['zyega_num1'].'/'.$row['zyega_num2'],
							'etc'=>$row['zzdecision_t'],	//7-하한율미만,0-유효업체,1-적격,3-심사대상,5-예가초과
							'etc2'=>$row['zzdecision'],	//7-하한율미만,0-유효업체,1-적격,3-심사대상,5-예가초과
						];
						if($row['num']==1) {
							$sucinfo['officeno1']			=$row['stcd2'];
							$sucinfo['officenm1']			=$row['name1'];
							$sucinfo['prenm1']				=$row['j_1kfrepre'];
							$sucinfo['success1']			=$row['zzvalue'];						
						}
						$sucinfo['succoms'][$seq]		=$succom;
						if($succom['etc2']==0 or $succom['etc2']==1 or $succom['etc2']==3) {
							$plus[]=$seq;
						}else{
							$minus[]=$seq;
						}
					}
					$i=1;
					foreach($plus as $seq) {
						$sucinfo['succoms'][$seq]['rank']=$i;
						$i++;
					}
					$i=count($minus)*-1;
					foreach($minus as $seq) {
						$sucinfo['succoms'][$seq]['rank']=$i;
						$i++;
					}
					$sucinfo['plus']=count($plus);
					$sucinfo['minus']=count($minus);
					$sucinfo['innum']=count($sucinfo['succoms']);
					//$sucinfo['bidproc']='S';

					//$sucinfo['succom']=$row['value'];
					
				}else if($row['name']=='ET_ZSMMEEBID0015') {
					$d=$row['value'][0];
					$multispares=[];
					$multicnt=[];
					
					if(!empty($d)) {
						foreach($d as $k=>$v) {
							//개찰된 복수예가
							preg_match('/^fcstprice(\d{2})$/',$k,$matchs);					
							if($matchs[1]){
								$multispares[$k]=$v;						
							}

							//선택복수예가 추첨횟수
							preg_match('/^zzfcst(\d{2})$/',$k,$cntmatchs);					
							if($cntmatchs[1]){						
								$multicnt[$k]=trim($v)==''?0:trim($v);						
							}
						}
						ksort($multispares);
						ksort($multicnt);
						$sucinfo['multispare']=join('|',$multispares);
						$sucinfo['multicnt']=join('|',$multicnt);
					}					
				}else if($row['name']=='ET_ZSMMEEBID0016') {
					$d=$row['value'];
					$selms=[];
					if(!empty($d)) {
						if(is_array($d)) {
							foreach($d as $v) {
								$selms[]=intval($v['zcnt']);
							}
							sort($selms);
							$sucinfo['selms']=join('|',$selms);
						}
					}				
					$sucinfo['selyega']=$row['value'];
				}
			}
  	}
  	return $sucinfo;
  }

	public function suc_work($data) {
		if($data!==null) {								
			$this->suc_sf($data);						
		}
	}

	public function suc_sf($data) {		
		$bidkey=BidKey::find()->where(['whereis'=>'52',])->andWhere("notinum like '{$data['notinum']}%'")->andWhere("notinum_ex = '{$data['notnum_ex']}'")->orderBy('bidid desc')->limit(1)->one();

		if($bidkey===null) {
			echo "\n not yet success or fail...wait!! \n";
			return;
		}
		if($data['bidproc']===null) {
			echo "\n bidproc is null \n";
			return;
		}
		if($bidkey->bidproc=='S' or $bidkey->bidproc=='F') {
			return;
		}
		
		$data['bidid']=$bidkey->bidid;
		//i2_auto_suc 호출
		$this->module->gman_do($this->i2_func,Json::encode($data));
	}
}

