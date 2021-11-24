<?php
namespace zion\phpdoc;

/**
 * Esta é uma classe exemplo. Lorem ipsum dolor sit amet, consectetur adipiscing elit, 
 * sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim 
 * veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo 
 * consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum 
 * dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, 
 * sunt in culpa qui officia deserunt mollit anim id est laborum
 * 
 * @author Vinicius Cesar Dias
 * @since 16/09/2015
 * @link http://www.teste.com
 * @version 1.0
 */
abstract class ClassSample1 extends ClassSample2 
implements InterfaceSample1, InterfaceSample2 {
	/**
	 * Comentário da constante 1. Comentário completo
	 * @author Vinicius
	 * @see teste
	 * @version 1.0
	 */
	const CONSTANTE1 = 1;
	
	/**
	 * Comentário da constante 2. Comentário completo
	 * @author Vinicius
	 * @see teste
	 * @version 2.0
	 */
	const CONSTANTE2 = "Constante 2 Teste";

	/**
	 * Comentário do teste public 1. Comentário completo
	 * @author Vinicius
	 */
	public $test1Public = "";
	public $test1Public2;
	
	/**
	 * Comentário do teste public 2. Comentário completo
	 * @author Vinicius
	 */
	public $test2Public = "Teste2 Atributo Publico";
	public $test3Public = false;
	
	/**
	 * Comentário do atributo privado 1. Comentário completo
	 * @author Vinicius
	 * @version 3.0
	 */
	private $test1Private;
	private $test2Private = null;
	private $test3Private = array();
	
	protected $test1Protected = true;
	protected $test2Protected = 15.556;
	protected $test3Protected = array("chave" => "valor");
	
	/**
	 * Comentário do atributo estatico. Comentário completo
	 * @author Vinicius
	 * @version 3.0
	 */
	public static $test1Static = 1;
	protected static $test2Static = ";;;;";
	private static $test3Static = array(
		"teste1" => array(
			array(1,2,3),
			array(";")
		)
	);
	
	/**
	 * Este é o construtor. Testando construtor
	 * @test1 $a int Teste
	 * @throws \Exception
	 */
    public function __construct(){
    }
    
    /**
	 * Este é o test1MetodoEstatico. Testando metodo
	 * @test1 $a int Teste
	 * @test2 $b string Teste
	 * @test3 $c int Teste
	 * @test4 $d int Teste
	 * @test5 $e int Teste
	 * @return String
	 * @throws \Exception
	 * @version 26.5
	 * @link www.teste.com.br
	 */
    public static function test1MetodoEstatico(){    	
    }
    
     /**
	 * Este é o test2MetodoEstatico. Testando metodo
	 * @test1 $a int Teste
	 * @test2 $b string Teste
	 * @test3 $c int Teste
	 * @test4 $d int Teste
	 * @test5 $e int Teste
	 * @return String
	 * @throws \Exception
	 * @version 26.5
	 * @link www.teste.com.br
	 */
    final public static function test2MetodoEstatico(){    	
    }
    
     /**
	 * Este é o test3MetodoEstatico. Testando metodo
	 * @test1 $a int Teste
	 * @test2 $b string Teste
	 * @test3 $c int Teste
	 * @test4 $d int Teste
	 * @test5 $e int Teste
	 * @return String
	 * @throws \Exception
	 * @version 26.5
	 * @link www.teste.com.br
	 */
    abstract public static function test3MetodoEstatico();
    
    /**
     * Testando
     * @param Teste
     * @return String
     */
    abstract public function test1Metodo($a=1,$b="teste",$c=null);
    
    final public function test2MetodoFinal(){    	
    }
    
    /**
     * Testando
     * @param Teste
     * @return String
     */
    public function __destruct(){    	
    }
}
?>