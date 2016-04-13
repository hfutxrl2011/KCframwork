<?php
class TestController extends Controller
{
	public function _before()
	{
		if('prod' == Env::getEnvironment()){
			//exit("0");
		}
	}

	public function mytestAction(){

     echo "hello world!!!";exit; 


   }
	
	
	
}
?>
