<?php
namespace djcp\korail\crawlers\bid;

use yii\helpers\Json;

use djcp\korail\models\BidKey;

class Crawler extends \djcp\korail\Crawler
{
  public $date1;
  public $date2;
  public $notinum;
  public $revision;

	public $module;
	private $i2_func='i2_auto_bid';


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
          ['name'=>'I_ZBIDINV','value_string'=>$this->notinum],
          ['name'=>'I_ZESTNUMM','value_string'=>$this->revision],
        ];
        $fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0010'];

        list($notinum,$revision)=explode('-',$this->notinum);				
				$data=$this->getDetail($p,$fn);
				
				if($data!==null) {
					if($data['bidproc_t']=='변경공고' and intval($revision)>0) {
						echo "-----------------bid_m \n";					
						$this->bid_m($data);
					}else if($data['bidproc_t']=='취소공고' and intval($revision)>0) {
						echo "-----------------bid_c \n";
						$this->bid_c($data);
					}else if($data['bidproc_t']=='재공고') {
						echo "-----------------bid_r \n";
						$this->bid_r($data);
					}else {					
						echo "-----------------bid_b \n";
						$this->bid_b($data);	
					}
				}	        
        sleep(1);
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
          ['name'=>'I_ZBIDINV','value_string'=>$this->notinum],
          ['name'=>'I_ZESTNUMM','value_string'=>$this->revision],
        ];
        $fn=['ZFUNCNM'=>'ZMME_EBID_INFO_0010'];

				list($notinum,$revision)=explode('-',$this->notinum);				
				// 상세가져오기
				$data=$this->getDetail($p,$fn);

				if($data!==null) {
					if($data['bidproc_t']=='변경공고' and intval($revision)>0) {
						echo "-----------------bid_m \n";					
						$this->bid_m($data);
					}else if($data['bidproc_t']=='취소공고' and intval($revision)>0) {
						echo "-----------------bid_c \n";
						$this->bid_c($data);
					}else if($data['bidproc_t']=='재공고') {
						echo "-----------------bid_r \n";
						$this->bid_r($data);
					}else {					
						echo "-----------------bid_b \n";
						$this->bid_b($data);	
					}
				}				

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

		/*$p2=[					  						
			['name'=>'I_ZZBIDINV','value_string'=>$row['notinum']],
			['name'=>'I_ZZSTNUM','value_string'=>$row['revision']],			
		];

		$fn2=['ZFUNCNM'=>'ZMME_EBID_BIDD_0009'];
		
    $license=$this->getLicense($p2,$fn2);
		*/
		//print_r($data);
		
    $answer=$callback($data);
		switch($answer) {
    case 'n': return;
    case 'y':
			if($data!==null) {
				if($data['bidproc_t']=='변경공고' and intval($revision)>0) {
					echo "-----------------bid_m \n";					
					$this->bid_m($data);
				}else if($data['bidproc_t']=='취소공고' and intval($revision)>0) {
					echo "-----------------bid_c \n";
					$this->bid_c($data);
				}else if($data['bidproc_t']=='재공고') {
					echo "-----------------bid_r \n";
					$this->bid_r($data);
				}else {					
					echo "-----------------bid_b \n";
					$this->bid_b($data);	
				}
			}
			break;
		}
  }

	public function bid_m($data) {
		list($notinum,$revision)=explode('-',$data['notinum']);
		$query=BidKey::find()->where(['whereis'=>'52',])->andWhere("notinum like '{$notinum}%'");
		$bidkey=$query->orderBy('bidid desc')->limit(1)->one();
		if($bidkey!==null) {
      list($notinum_p,$revision_p)=explode('-',$bidkey->notinum);
			if(intval($revision_p)<intval($revision)) {
        list($a,$b,$c,$d)=explode('-',$bidkey->bidid);
        $b=sprintf('%02s',intval($b)+1);
        $data['bidid']="$a-$b-$c-$d";
        $data['bidproc']='M';
			
				// i2_auto_bid 호출	
				$this->module->gman_do($this->i2_func,Json::encode($data));
			}
		}
		
	}
	public function bid_c($data) {
		list($notinum,$revision)=explode('-',$data['notinum']);
		$query=BidKey::find()->where(['whereis'=>'52',])->andWhere("notinum like '{$notinum}%'");
		$bidkey=$query->orderBy('bidid desc')->limit(1)->one();
		if($bidkey!==null and $bidkey->bidproc!='C') {
      list($a,$b,$c,$d)=explode('-',$bidkey->bidid);
      $b=sprintf('%02s',intval($b)+1);
      $data['bidid']="$a-$b-$c-$d";
      $data['bidproc']='C';
			
			// i2_auto_bid 호출
			$this->module->gman_do($this->i2_func,Json::encode($data));
		}
		
	}
	public function bid_r($data) {
		list($notinum,$revision)=explode('-',$data['notinum']);
		$query=BidKey::find()->where(['whereis'=>'52',])->andWhere("notinum like '{$notinum}%'");
		$bidkey=$query->orderBy('bidid desc')->limit(1)->one();
		if($bidkey!==null) {
			list($a,$b,$c,$d)=explode('-',$bidkey->bidid);
			if(intval($revision)>intval($c)) {
				$b=sprintf('%02s',intval($b)+1);
				$c=sprintf('%02s',intval($revision));
				$data['previd']=$bidkey->bidid;
        $data['bidid']="$a-$b-$c-$d";
        $data['bidproc']='R';
        $data['constnm']=$data['constnm'].'//재투찰';
				
				// i2_auto_bid 호출 
				$this->module->gman_do($this->i2_func,Json::encode($data));
			}
		}

	}
	public function bid_b($data) {
		list($notinum,$revision)=explode('-',$data['notinum']);
		$query=BidKey::find()->where(['whereis'=>'52',])->andWhere("notinum like '{$notinum}%'");
		$bidkey=$query->orderBy('bidid desc')->limit(1)->one();
		if($bidkey===null) {			
			$data['bidid']=sprintf('%s%s-00-00-01',date('ymdHis'),str_pad(mt_rand(0,999),3,'0',STR_PAD_LEFT));
			$data['bidproc']='B';
			
			// i2_auto_bid 호출
			$this->module->gman_do($this->i2_func,Json::encode($data));		
		}
	}

  public function getDetail(array $p,array $fn){
    $p=Json::encode($p);
    $fn=Json::encode($fn);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);

		$itab=$result['itab'];
		
    $bidinfo=[]; //입찰정보

		$location = array(
			"전국"						=>	0,												
			"서울특별시"			=>	1,
			"부산광역시"			=>	2,
			"광주광역시"			=>	3,
			"대전광역시"			=>	4,
			"인천광역시"			=>	5,
			"대구광역시"			=>	6,
			"울산광역시"			=>	7,
			"경기도"					=>	8,
			"강원도"					=>	9,
			"충청북도"				=>	10,
			"충청남도"				=>	11,
			"경상북"					=>	12,
			"경상남"					=>	13,
			"전라북"					=>	14,
			"전라남"					=>	15,
			"제주특별자치도"	=>	16,
			"세종특별자치시"	=>	17,
		);		
		
    foreach($itab as $row){
      if($row['name']=='ET_DOCLIST'){
        $bidinfo['files']=$row['value'];

				if(is_array($bidinfo['files'])){
					foreach($bidinfo['files'] as $file){
						$filename=str_replace('#','-',$file['filnm']).'#http://http://ebidn.korail.com:50000/file/fileDownload?doknr='.$file['doknr'].'&fileidx='.$file['flidx'];
						$files[]=$filename;
					}
				}

				$bidinfo['attchd_lnk']=join('|',$files);

      }else if($row['name']=='ET_ZSMMEEBID0004'){
        $d=$row['value'][0];

				$bidinfo['bidproc_t']		=$d['zzbidinfo_t'];																											//공고정보(재공고,취소공고,변경공고
        $bidinfo['constnm']			=$d['description'];																											//입찰공고명
        $bidinfo['bidtype']			=($d['zzbidtypecode']=='5')?'ser':'pur';																//물품(내자)=1,물품(외자)=9				
				$bidinfo['notinum']			=$d['zzbidinv'];																												//입찰공고번호	
				$bidinfo['old_notinum']	=$d['zzbidinv_old'];																										//기존공고번호(재공고,변경공고시)
				//계약방법(1-일반경쟁,2-제한경쟁,4-수의계약)
				$bidinfo['contract']		=($d['zzctrmethod']=='1')?'10':($d['zzctrmethod']=='2')?'20':($d['zzctrmethod']=='4')?'40':'';
				//입찰방법(A-전자입찰)
				$bidinfo['bidcls']			=($d['zzbidmethod']=='A')?'01':'';
				//낙찰자선정방법(A005-협상에의한계약,D008-제한적최저가,A001-최저가격낙찰제)
				if($d['zzwayofbidding']=='A005'){
					$bidinfo['succls']		='07';
				}else if($d['zzwayofbidding']=='D008'){
					$bidinfo['succls']		='03';
				}else if($d['zzwayofbidding']=='A001'){
					$bidinfo['succls']		='02';
				}else if($d['zzwayofbidding']=='J011') {
					$bidinfo['succls']		='01';
				}
				$bidinfo['convention']	=($d['zzcom']=='Y')?'2':'';//i2 0해당없음 1의무 2가능 3의무+가능															//공동수급

				if($d['zzprcmethod']=='M'){
					$bidinfo['yegatype']	='25';																																			//예가방식
					$bidinfo['yegarng']		=$d['low'].'|'.$d['high'];																									//예가범위
				}

				$bidinfo['basic']				=$d['fcastprice'];																													//기초금액				
				$bidinfo['opendt']			=date('Y-m-d H:i:s',strtotime($d['zzbid_sdat'].' '.$d['zzbid_stim']));			//입찰개시일시
				$bidinfo['closedt']			=date('Y-m-d H:i:s',strtotime($d['zzbid_edat'].' '.$d['zzbid_etim']));			//입찰마감일시
				$bidinfo['constdt']			=date('Y-m-d H:i:s',strtotime($d['zzopen_dat'].' '.$d['zzopen_tim']));			//개찰일시
				$bidinfo['noticedt']		=date('Y-m-d H:i:s',strtotime($d['zzbid_erdat'].' '.$d['zzbid_ertim']));		//공고게시일
				$bidinfo['agreedt']			=date('Y-m-d H:i:s',strtotime($d['zzbidappl_dat'].' '.$d['zzbidappl_tim']));//공동수급협정서 접수마감일시
				$bidinfo['charger']			=$d['zzpernr_t'].'|'.$d['zztelno'];																					//담당자정보
				$bidinfo['pct']					=$d['zzselrate'];																														//낙찰하한율
        
				//제한지역
				if($d['zzland1_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland1_t']]);
				if($d['zzland2_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland2_t']]);
				if($d['zzland3_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland3_t']]);
				if($d['zzland4_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland4_t']]);
				if($d['zzland5_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland5_t']]);
				if($d['zzland6_t']!="")	$bidinfo['location']	+=pow(2,$location[$d['zzland6_t']]);

				//state
				$bidinfo['state']				= 'N';
				$bidinfo['whereis']			= '52';
        //
        //
        //
      }
    }

    return $bidinfo;
  }

	public function getLicense(array $p, array $fn) {
    $p=Json::encode($p);
    $fn=Json::encode($fn);
    $result=$this->get('/gateway/gateway',['p'=>$p,'fn'=>$fn]);

		$itab=$result['itab'];
		
		foreach($itab as $row) {		
			if($row['name']=='ET_ZSMMEEBID0020') {
					$licenseinfo=[];
					foreach($row['value'] as $r) {
						$licenseinfo['license']=$r;	
					}
					return $licenseinfo;
			}
		}
		return [];
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

