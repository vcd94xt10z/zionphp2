<?php
namespace zion\utils;
// http://www.ebrueggeman.com/blog/php_execution_time 28/05/2014
/**
 * @author Vinicius Cesar Dias
 */
class TimeCounter {
	private static $data = array();
	
    public static function getStartTime($key,$output="mili"){
    	return self::convertTime("mili",$output,self::$data[$key]["startTime"]);
    }
    
    public static function getEndTime($key,$output="mili"){
    	return self::convertTime("mili",$output,self::$data[$key]["endTime"]);
    }
    
    public static function getTimestamp(){
        $timeofday = gettimeofday();
        //RETRIEVE SECONDS AND MICROSECONDS (ONE MILLIONTH OF A SECOND)
        //CONVERT MICROSECONDS TO SECONDS AND ADD TO RETRIEVED SECONDS
        //MULTIPLY BY 1000 TO GET MILLISECONDS
         return 1000*($timeofday['sec'] + ($timeofday['usec'] / 1000000));
    }
    
    public static function start($key){
        self::$data[$key]["startTime"] = self::getTimestamp();
        self::$data[$key]["begin"] = new \DateTime();
    }
    
    public static function stop($key){
        self::$data[$key]["endTime"] = self::getTimestamp();
        self::$data[$key]["end"] = new \DateTime();
    }
    
    public static function begin($key){
    	return self::$data[$key]["begin"];
    }
    
    public static function end($key){
    	return self::$data[$key]["end"];
    }
    
    public static function getData($key){
    	return self::$data[$key];
    }
    
    public static function getAllData(){
        return self::$data;
    }
    
    public static function duration($key,$output="mili"){
    	$mili = floatval(number_format((self::$data[$key]["endTime"])-(self::$data[$key]["startTime"]),2,'.',''));
    	return self::convertTime("mili",$output,$mili);
    }
    
    public static function convertTime($input,$output,$time){
    	if($input == $output){
    		return $time;
    	}
    	
    	// convertendo entrada para mili (unidade minima)
    	switch($input){
        case "sec":
        	$time *= 1000;
        	break;
        case "min":
        	$time *= 1000 * 60;
        	break;
        }
        
        // convertendo saida
        switch($output){
        case "sec":
        	return $time / 1000;
        	break;
        case "min":
        	return $time / 1000 / 60;
        	break;
        }
        
        return null;
    }
}
?>