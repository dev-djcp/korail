<?php
namespace djcp\korail\console;

use yii\helpers\Console;

class CrawlerAction extends \yii\base\Action
{
  public $crawler;

  public function run(){
    $this->controller->stdout(Console::renderColoredString("Running %y{$this->crawler->mode}%n mode '{$this->crawler->getName()}'...\n\n"));

    $this->crawler->scenario=$this->crawler->mode;
    if($this->crawler->validate()){
      $this->runCrawler();
    }
    else{
      $this->displayValidateErrors();
    }
  }

  protected function displayValidateErrors(){
    $this->controller->stdout("Crawler not started. Please fix the following errors:\n\n",Console::FG_RED);
    foreach($this->crawler->errors as $attributes=>$errors){
      echo ' - '.$this->controller->ansiFormat($attributes,Console::FG_CYAN).': '.implode(';',$errors)."\n";
    }
    echo "\n";
  }

  protected function runCrawler(){
    //
    // 검색 실행
    //
    if($this->crawler->mode==='search'){
      $this->crawler->search(function($data){
        foreach($data as $row){
          print_r($row);
        }
        $answer=$this->controller->select('자료를 입력하시겠습니까?',[
          'n'=>'작업을 취소합니다.',
          'y'=>'미입력 공고만 입력합니다.',
          'a'=>'모든 공고를 입력합니다.',
        ]);
        return $answer;
      });
    }
    //
    // 감시 실행
    //
    else if($this->crawler->mode==='watch'){
      $this->crawler->watch(function($data){
        print_r($data);
      });
    }
    //
    // 상세정보
    //
    else if($this->crawler->mode==='detail'){
      $this->crawler->detail(function($data){
        print_r($data);
        $answer=$this->controller->select('자료를 입력하시겠습니까?',[
          'n'=>'작업을 취소합니다.',
          'y'=>'작업을 실행합니다.',
        ]);
        return $answer;
      });
    }
  }
}

