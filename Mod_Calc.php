<?php

class pHeader{
	var $people;
	var $nameList;
	function __construct(array $names){
		$this->people = count($names);
		$this->nameList = $names;
	}
	function findIndex(string $name){
		return array_search($name,$this->nameList);
	}
}

class transaction{
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
            var_dump($payment);
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
	var $head;
	var $transNum;
	var $transList;
	var $paySum;
	var $dueSum;
}

$h = new pHeader(array('a','b','c'));
echo $h->findIndex('a');

?>