<?php
namespace zion\utils;

use DateTime;
use zion\core\System;

/**
 * @author Vinicius Cesar Dias
 */
class DateTimeUtils {
    public static function between(DateTime $date,DateTime $ini, DateTime $fim){
        if($date == null || $ini == null || $fim == null){
            return false;
        }

        $date_i = strtotime($date->format("Y-m-d H:i:s"));
        $ini_i  = strtotime($ini->format("Y-m-d H:i:s"));
        $fim_i  = strtotime($fim->format("Y-m-d H:i:s"));

        if($date_i > $ini_i && $date_i < $fim_i) {
            return true;
        } else {
            return false;
        }
    }

	public static function parseDate($dateString,$format){
		$data = date_parse_from_format($format, $dateString);
		if($data["error_count"] > 0){
			return null;
		}

		// ajustando para não dar pau quando não tiver horário
		if($data["hour"] === false){
			$data["hour"] = '00';
		}
		if($data["minute"] === false){
			$data["minute"] = '00';
		}
		if($data["second"] === false){
			$data["second"] = '00';
		}

		try {
			return new DateTime($data["year"]."-".$data["month"]."-".$data["day"]
					." ".$data["hour"].":".$data["minute"].":".$data["second"]);
		}catch(\Exception $e){
			return null;
		}
	}

	public static function parseTimezone($timezone){
		// formato +00:00
		if(mb_strlen($timezone) != 6){
			return null;
		}
		$signal = mb_substr($timezone,0,1);
		$hour = intval(mb_substr($timezone,1,2));
		$minute = intval(mb_substr($timezone,4,2));

		// validando
		if(($signal != "+" && $signal != "-") || ($hour < -14 || $hour > 14) || ($minute < 0 || $minute > 60)){
			return null;
		}

		return array(
			"signal" => $signal,
			"hour" => $hour,
			"minute" => $minute
		);
	}

	/**
	 * Converte uma data de um timezone para outro
	 */
	public static function convertTZ(DateTime $date,$fromTZ,$toTZ){
		if(!($date instanceof DateTime)){
			return null;
		}

		// não precisa de conversão de fuso horário
		if($fromTZ == $toTZ){
			return $date;
		}

		$phpDateTimeFormat = "Y-m-d H:i:s";

		// convertendo data para +00:00
		$fromTZ = self::parseTimezone($fromTZ);
		$toTZ = self::parseTimezone($toTZ);

		// invertendo sinal para chegar a UTC+00:00
		$fromTZ["signal"] = ($fromTZ["signal"]=='+')?'-':'+';
		$fromTZMinutes = intval($fromTZ["signal"].(($fromTZ["hour"]*60)+$fromTZ["minute"]));

		// os minutos do timezone destino não precisa alterar o sinal
		$toTZMinutes = intval($toTZ["signal"].(($toTZ["hour"]*60)+$toTZ["minute"]));

		// considerando que a origem já esta em UTC0, tira a diferença
		$resultTZMinutes = $fromTZMinutes + $toTZMinutes;

		// aplicando diferença
		$tmp = $date->format("Y-m-d H:i:s")." ".$resultTZMinutes." minutes";
		$newDate = date("Y-m-d H:i:s",strtotime($tmp));
		return new DateTime($newDate);
	}

	public static function convertDateFormat($dateString,$formatIn,$formatOut){
		$dateString = preg_replace("/[^0-9]/","",$dateString);
		$formatIn = str_split($formatIn);

		$index = 0;
		$length = 0;

		$size = array(
			'd' => 2,
			'm' => 2,
			'Y' => 4,
			'H' => 2,
			'i' => 2,
			's' => 2,
		);

		$data = array(
			'd' => 0,
			'm' => 0,
			'Y' => 0,
			'H' => 0,
			'i' => 0,
			's' => 0,
		);

		// extraindo dados
		foreach($formatIn AS $char){
			switch($char){
			case 'd':
			case 'm':
			case 'H':
			case 'i':
			case 's':
				$length = $size[$char];
				$data[$char] = intval(mb_substr($dateString,$index,$length));
				$index += $length;
				break;
			case 'Y':
				$length = $size[$char];
				$data[$char] = intval(mb_substr($dateString,$index,$length));
				$index += $length;
				break;
			}
		}

		// gerando data no formato solicitado
		foreach($data AS $k => $v){
			$length = $size[$k];
			$vFormatted = str_pad($v,$length,"0",STR_PAD_LEFT);
			$formatOut = str_replace($k,$vFormatted,$formatOut);
		}

		return $formatOut;
	}

    /**
	 * Retorna um array com as datas do intervalo
	 * @param string since
	 * @param string until
	 * @param string step
	 * @param string date format
	 * @return array
	 * @author Ali OYGUR <alioygur@gmail.com>
	 */
	public static function dateRange($first, $last, $step = '+1 day', $format = 'd/m/Y' ) {
	    $dates = array();
	    $current = strtotime($first);
	    $last = strtotime($last);

	    while( $current <= $last ) {
			$dates[] = date($format, $current);
			$current = strtotime($step, $current);
	    }
	    return $dates;
	}

	/**
	 * Retorna o numero de segundos que uma data tem
	 */
    public static function getDatetimeSeconds(DateTime $dateTime){
    	return mktime($dateTime->format("H"), $dateTime->format("i"), $dateTime->format("s"),
    			$dateTime->format("m"), $dateTime->format("d"), $dateTime->format("Y"));
    }

    /**
     * Retorna a diferença em segundos das duas datas
     */
    public static function getSecondsDiff(DateTime $finalDate, DateTime $initialDate){
    	$tempFinal = $finalDate->format("Y-m-d H:i:s");
    	$tempInitial = $initialDate->format("Y-m-d H:i:s");

    	$finalSeconds = strtotime($tempFinal);
    	$initialSeconds = strtotime($tempInitial);

    	return $finalSeconds - $initialSeconds;
    }

    /**
     * Subtrai duas datas e retorna o array com a diferença em horas até segundos
     */
    public static function subtract(DateTime $finalDate, DateTime $initialDate){
    	$tempFinal = $finalDate->format("Y-m-d H:i:s");
    	$tempInitial = $initialDate->format("Y-m-d H:i:s");

    	$finalSeconds = strtotime($tempFinal);
    	$initialSeconds = strtotime($tempInitial);

    	$totalSeconds = $finalSeconds - $initialSeconds;

    	$diffHours = intval($totalSeconds / 3600);
    	$diffMinutes = intval(($totalSeconds / 60) - ($diffHours * 60));
    	$diffSeconds = $totalSeconds - ($diffMinutes * 60) - ($diffHours * 3600);

    	return array(
    		"Hours" => $diffHours,
    		"Minutes" => $diffMinutes,
    		"Seconds" => $diffSeconds
    	);
    }

    /**
     * Soma duas datas e retorna um array com o resultado de horas até minutos
     */
    public static function sum(DateTime $finalDate, DateTime $initialDate){
    	$tempFinal = $finalDate->format("Y-m-d H:i:s");
    	$tempInitial = $initialDate->format("Y-m-d H:i:s");

    	$finalSeconds = strtotime($tempFinal);
    	$initialSeconds = strtotime($tempInitial);

    	$totalSeconds = $finalSeconds + $initialSeconds;

    	$diffHours = intval($totalSeconds / 3600);
    	$diffMinutes = intval(($totalSeconds / 60) - ($diffHours * 60));
    	$diffSeconds = $totalSeconds - ($diffMinutes * 60) - ($diffHours * 3600);

    	return array(
    		"Hours" => $diffHours,
    		"Minutes" => $diffMinutes,
    		"Seconds" => $diffSeconds
    	);
    }

    /**
     * Formata o tempo em segundos de forma textual
     * @param int $diff segundos
     */
    public static function formatDiff($diff){
        $isNegative = ($diff < 0);
        $diff = abs($diff);

        $result = self::formatTimeBySeconds($diff,"array","auto");

        if($result["centuries"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["centuries"]." século(s)";
        	}else{
        		return $result["centuries"]." século(s) atrás";
        	}
        }
        if($result["years"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["years"]." ano(s) e ".$result["months"]." mês(es)";
        	}else{
        		return $result["year"]." ano(s) e ".$result["months"]." mês(es) atrás";
        	}
        }
        if($result["months"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["months"]." mês(es) e ".$result["days"]." dia(s)";
        	}else{
        		return $result["months"]." mês(es) e ".$result["days"]." dia(s) atrás";
        	}
        }
        if($result["days"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["days"]." dia(s) e ".$result["hours"]." hora(s)";
        	}else{
        		return $result["days"]." dia(s) e ".$result["hours"]." hora(s) atrás";
        	}
        }
		if($result["hours"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["hours"]." hora(s) e ".$result["minutes"]." minuto(s)";
        	}else{
        		return $result["hours"]." hora(s) e ".$result["minutes"]." minuto(s) atrás";
        	}
        }
        if($result["minutes"] > 0){
        	if($isNegative){
        		return "Daqui a ".$result["minutes"]." minutos(s) e ".$result["seconds"]." segundo(s)";
        	}else{
        		return $result["minutes"]." minuto(s) e ".$result["seconds"]." segundo(s) atrás";
        	}
        }

        // segundos
    	if($isNegative){
    		return "Daqui a ".$result["seconds"]." segundo(s)";
    	}else{
    		return $result["seconds"]." segundo(s) atrás";
    	}
    }

    /**
     * Formata a diferença entre duas datas
     * @param DateTime $finalDate Primeira data
     * @param DateTime $initialDate Segunda data
     * @param bool $displayEmptyScales Exibe escalas vazias
     * @param String $format Formato da saída {H:m:s,Hhmmss}
     * @return String diferença formatada
     */
    public static function diff(DateTime $finalDate, DateTime $initialDate, $displayEmptyScales=false,$format="Hhmmss"){
    	$result = self::subtract($finalDate,$initialDate);
    	$diffHours = abs($result["Hours"]);
    	$diffMinutes = abs($result["Minutes"]);
    	$diffSeconds = abs($result["Seconds"]);
    	$output = "";

    	// formatando a saída
    	if($format == "Hhmmss"){
    		if($displayEmptyScales){
	    		$output .= $diffHours."h";
		    	$output .= $diffMinutes."m";
		    	$output .= $diffSeconds."s";
	    	}else{
		    	if($diffHours > 0){
		    		$output .= $diffHours."h";
		    	}
		    	if($diffHours > 0 || $diffMinutes > 0){
		    		$output .= $diffMinutes."m";
		    	}
		    	if($diffHours > 0 || $diffSeconds > 0){
		    		$output .= $diffSeconds."s";
		    	}
	    	}
    	}else{
    		$output = $diffHours.":".$diffMinutes.":".$diffSeconds;
    	}
    	return $output;
    }

    /**
     * @param string $output {time,text,array}
     * @param string $maxScale {c,y,M,d,h,m,s,auto}
     */
    public static function formatTimeBySeconds($seconds,$output="time",$maxScale="auto"){
    	$seconds = abs(intval($seconds));

    	// valores para conversao para segundos
        $minToSec   = 60; 		  // 1 minuto
        $hourToSec  = 3600; 	  // 1 hora
        $dayToSec   = 86400; 	  // 1 dia
        $monthToSec = 2592000;    // 1 mes de 30 dias
        $yearToSec  = 31118400;   // 1 ano de 365 dias e 4 horas
        $centToSec  = $yearToSec * 100; // 100 anos

    	// séculos
    	$centuries = floor($seconds / $centToSec);
    	$seconds -= $centuries * $centToSec;

    	// anos
    	$years = floor($seconds / $yearToSec);
    	$seconds -= $years * $yearToSec;

    	// meses
    	$months = floor($seconds / $monthToSec);
    	$seconds -= $months * $monthToSec;

    	// dias
    	$days = floor($seconds / $dayToSec);
    	$seconds -= $days * $dayToSec;

    	// horas
    	$hours = floor($seconds / $hourToSec);
    	$seconds -= $hours * $hourToSec;

    	// minutos
    	$minutes = floor($seconds / $minToSec);
    	$seconds -= $minutes * $minToSec;

    	if($maxScale == "auto"){
    		if($centuries > 0){
    			$maxScale = "c";
    		}else if($years > 0){
    			$maxScale = "y";
    		}else if($months > 0){
    			$maxScale = "M";
    		}else if($days > 0){
    			$maxScale = "d";
    		}else if($hours > 0){
    			$maxScale = "h";
    		}else if($minutes > 0){
    			$maxScale = "m";
    		}else{
    			$maxScale = "s";
    		}
    	}

    	if($output == "time"){
    		// zero fill
    		if($hours < 10){
	    		$hours = "0".$hours;
	    	}
	    	if($minutes < 10){
	    		$minutes = "0".$minutes;
	    	}
	    	if($seconds < 10){
	    		$seconds = "0".$seconds;
	    	}

	    	switch($maxScale){
	    	case "c":
	    		return $centuries.":".$years.":".$months.":".$days.":".$hours.":".$minutes.":".$seconds;
	    		break;
	    	case "y":
	    		return $years.":".$months.":".$days.":".$hours.":".$minutes.":".$seconds;
	    		break;
	    	case "M":
	    		return $months.":".$days.":".$hours.":".$minutes.":".$seconds;
	    		break;
	    	case "d":
	    		return $days.":".$hours.":".$minutes.":".$seconds;
	    		break;
	    	case "h":
	    		return $hours.":".$minutes.":".$seconds;
	    		break;
	    	case "m":
	    		return $minutes.":".$seconds;
	    		break;
	    	case "s":
	    		return $seconds;
	    		break;
	    	}
    	}else if($output == "array"){
	    	return array(
	    		"centuries" => $centuries,
	    		"years" 	=> $years,
	    		"months" 	=> $months,
	    		"days" 		=> $days,
	    		"hours" 	=> $hours,
	    		"minutes" 	=> $minutes,
	    		"seconds" 	=> $seconds
	    	);
    	}elseif($output == "short"){
    		$output = "";
    		if($centuries > 0){
    			$output .= $centuries."s";
    		}
    		if($years > 0){
    			$output .= $years."a";
    		}
    		if($months > 0){
    			$output .= $months."n";
    		}
    		if($days > 0){
    			$output .= $days."d";
    		}
    		if($hours > 0){
    			$output .= $hours."h";
    		}
    		if($minutes > 0){
    			$output .= $minutes."m";
    		}
    		if($seconds > 0){
    			$output .= $seconds."s";
    		}
    		return trim($output);
    	}else{
    		$output = "";
    		if($centuries > 0){
    			$output .= $centuries." século(s) ";
    		}
    		if($years > 0){
    			$output .= $years." ano(s) ";
    		}
    		if($months > 0){
    			$output .= $months." mês(es) ";
    		}
    		if($days > 0){
    			$output .= $days." dia(s) ";
    		}
    		if($hours > 0){
    			$output .= $hours." hora(s) ";
    		}
    		if($minutes > 0){
    			$output .= $minutes." minuto(s) ";
    		}
    		if($seconds >= 0){
    			$output .= $seconds." segundo(s) ";
    		}
    		return trim($output);
    	}
    }

    /**
     * Retorna a diferença na unidade informada
     * @param string $unit {c,y,d,M,y,h,m,s}
     */
    public static function getDiffByUnit(DateTime $d1, DateTime $d2,$unit="d"){
    	$format = "Y-m-d H:i:s";
    	$date1 = $d1->format($format);
		$date2 = $d2->format($format);

		$ts1 = strtotime($date1);
		$ts2 = strtotime($date2);

		$secondsDiff = abs($ts2 - $ts1);
		$result = self::formatTimeBySeconds($secondsDiff,"array","auto");
		switch($unit){
		case "c":
			return $result["centuries"];
			break;
		case "y":
			return $result["years"];
			break;
		case "M":
			return $result["months"];
			break;
		case "d":
			return $result["days"];
			break;
		case "h":
			return $result["hours"];
			break;
		case "m":
			return $result["minutes"];
			break;
		case "s":
		default:
			return $result["seconds"];
			break;
		}
    }

    public static function convertDateFormatPHPtoJS(){
    	$format = System::get("dateFormat");
    	$format = str_replace("d","dd",$format);
    	$format = str_replace("m","mm",$format);
    	$format = str_replace("Y","yy",$format);
    	return $format;
    }
}
?>
