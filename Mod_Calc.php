<?php

require "Mod_Lang.php";

// SHOULD BE REPLACED BY A GLOBAL ONE
$LANG = new langAssets("EN");

class pHeader{
	var $people;    //Number of people involved
	var $nameList;  //People's namelist (in order)
	function __construct(array $names){
		$this->people = count($names);
		$this->nameList = $names;
	}
	function findIndex(string $name){
		return array_search($name,$this->nameList);
	}
}

class trans{
    var $item;		//Name of transaction
    var $people;	//Number of people involved (should be globally identical)
    var $paid;		//Array indicates each person's payment
    var $consume;	//Array indicates how much should each person pay
	
    function __construct(string $item_name, int $number_of_persons){
        $this->item = $item_name;
        $this->people = $number_of_persons;
    }
    function record(array $payment, $due_ratio = 'even'){
        /* 
		 * $payment will be directly piped into $paid and be used for
		 * sum calculation.
		 * $due_ration can be 'even' or an array, indicates each person's
		 * share. array(1,1,1) equals to 'even' when 3 people involved.
		 * Normally returns 0, returns -n while nth parameter goes wrong.
		 */
        if (count($payment) == $this->people){
            $this->paid = $payment;
        }
        else return -1;
        if ($due_ratio == 'even'){
            $this->consume = array_fill(0, $this->people, 
                floatval($this->getSum()) / floatval($this->people));
        }
        elseif (is_array($due_ratio) && count($due_ratio) == $this->people){
            $t = 0.0;
            foreach($due_ratio as $i) $t += $i;
            $m = floatval($this->getSum()) / $t;
            for ($i = 0; $i < $this->people; $i++)
                $this->consume[$i] = $m * $due_ratio[$i];
        }
        else return -2;
        return 0;
    }
    function getSum(){
        $s = 0;
        foreach($this->paid as $i) $s += $i;
        return $s;
    }
}

class transLog{
	var $head;      //People's details as a log header (pHeader)
	var $transNum;  //Number of transactions
	var $transList; //Array of transactions (contain object trans)
	var $paySum;    //Array of every persons' total payment
	var $dueSum;    //Array of every persons' total expenses
    var $netSum;    //Array of every persons' net bill (- as in debt)

    function __construct(pHeader $people_log_header){
        $this->head = $people_log_header;
        $this->transNum = 0;
        $this->transList = array();
        $this->paySum = array_fill(0, $this->head->people, 0.0);
        $this->dueSum = array_fill(0, $this->head->people, 0.0);
        $this->netSum = array_fill(0, $this->head->people, 0.0);
    }
    function setTransList(array $transaction_list){
        $this->transNum = count($transaction_list);
        $this->transList = $transaction_list;
        $this->updateSums();
    }
    function addTransLog(trans $transaction){
        $this->transNum++;
        $this->transList[] = $transaction;
        for($i = 0; $i < $this->head->people; $i++){
            $this->paySum[$i] += $transaction->paid[$i];
            $this->dueSum[$i] += $transaction->consume[$i];
            $this->netSum[$i] = $this->paySum[$i] - $this->dueSum[$i];
        }
    }
    function updateSums(){
        if ($this->transNum == 0) return -1;
        $this->paySum = array_fill(0, $this->head->people, 0.0);
        $this->dueSum = array_fill(0, $this->head->people, 0.0);
        foreach($this->transList as $tr){
            for($i = 0; $i < $this->head->people; $i++){
                $this->paySum[$i] += $tr->paid[$i];
                $this->dueSum[$i] += $tr->consume[$i];
            }
        }
        for($i = 0; $i < $this->head->people; $i++){
            $this->netSum[$i] = $this->paySum[$i] - $this->dueSum[$i];
        }
    }
    function getSumsByIndex(int $index, $net_only = false){
        if ($net_only){
            return $this->netSum[$index];
        }
        else {
            return array($this->paySum[$index], $this->dueSum[$index], 
                $this->netSum[$index]);
        }
    }
    function getSumsByName(string $name, $net_only = false){
        //PLEASE AVOID USING SAME NAMES!! (array_search may return the first one)
        $id = array_search($name, $this->head->nameList);
        if ($id >= 0 && $id < $this->head->people)
            return $this->getSumsByIndex($id, $net_only);
    }
}

class calcRepay{
    var $tl;        //Transaction log on which calc is performed
    var $repayPref; //Matrix indicates the preference of repaying
    var $repayAmt;  //Matrix of actual amount of repaying money
    var $choiceLst; //Dictionary of the repay choices
    var $netSums;   //Array of people's net bills

    function __construct(transLog $transaction_log){
        $this->tl = $transaction_log;
        $this->repayAmt = NULL;
        $this->netSums = $this->getNetSums();
        $this->initPref();
    }
    function getNetSums(){
        $this->netSums = array();
        for($i = 0; $i < $this->tl->head->people; $i++){
            $this->netSums[] = $this->tl->getSumsByIndex($i, true);
        }
    }
    function initPref(){
        $this->repayPref = array_fill(0, $this->tl->head->people, 
            array_fill(0, $this->tl->head->people, -1));
        for($i = 0; $i < $this->tl->head->people; $i++){
            for($j = 0; $j < $this->tl->head->people; $j++){
                if ($i == $j || $this->netSums[$i] * $this->netSums[$j] >= 0)
                    continue;
                else{
                    $this->repayPref[$i][$j] = 0;
                    if ($this->netSums[$i] > 0){    //i>0, j<0, j pay i money
                        $this->choiceLst[$i * $this->tl->head->people + $j] = 
                            $this->tl->head->nameList[$j].$LANG->crtLang['pay'].
                            $this->tl->head->nameList[$i].$LANG->crtLang['money'];
                    }
                    else{                           //i<0, j>0, j pay i money
                        $this->choiceLst[$i * $this->tl->head->people + $j] = 
                            $this->tl->head->nameList[$i].$LANG->crtLang['pay'].
                            $this->tl->head->nameList[$j].$LANG->crtLang['money'];
                    }
                }
            }
        }
    }
    function regPref(array $choice_feedback){
        while($k = array_search(0, $choice_feedback))
            unset($choice_feedback[$k]);
        // MUST ADD PROTECTION IN UI TO PREVENT DUPLICATE CHOICES
        // AND CHOICES MUST IN CONTINUOUS SEQUENCE
        $choice_feedback = array_flip(array_unique($choice_feedback));
        foreach($choice_feedback as $c){
            $j = $c % $this->tl->head->people;
            $i = (int)(($c - $j) / $this->tl->head->people);
            $this->repayPref[$i][$j] = array_search($c, $choice_feedback);
        }
    }
}

//Test code

$h = new pHeader(array('a', 'b', 'c'));
$l = new transLog($h);

$t1 = new trans('t1', $h->people);
$t1->record(array(10, 0, 80));
$t2 = new trans('t2', $h->people);
$t2->record(array(50, 0, 20), array(1, 0, 2));

$tg = array($t1, $t2);
$l->setTransList($tg);

var_dump($l->getSumsByIndex(1));
var_dump($l->getSumsByIndex(2, true));
var_dump($l->getSumsByName('a'));

$t3 = new trans('t3', $h->people);
$t3->record(array(30,30,40), 'even');
$l->addTransLog($t3);

var_dump($l->getSumsByIndex(1));
var_dump($l->getSumsByIndex(2, false));
var_dump($l->getSumsByName('a'));

$a = array(12=>0,35=>0,44=>1);
var_dump($a);
var_dump(array_search(2,$a));
var_dump(array_unique($a));
?>