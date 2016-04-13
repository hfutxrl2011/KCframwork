<?php
class RSAEncoder
{
	public static function rsaEncode($source)
	{
		$result = "";
		$index = 0;
		$input = substr($source, $index, EnvConf::$rsaConf['max_en_length']);
		while($input){
			$out = "";
			if(!openssl_public_encrypt( $input, $out, EnvConf::$rsaConf['rsa_public_key'] )){
				return "";
			}
			$result.= $out;
			$index += EnvConf::$rsaConf['max_en_length'];
			$input = substr($source, $index, EnvConf::$rsaConf['max_en_length']);
		}
		return $result;
	}

	public static function rsaDecode($source)
	{
		$result = "";
        $index = 0;
        $input = substr($source, $index, EnvConf::$rsaConf['max_de_length']);
        while($input){
            $out = "";
            if(!openssl_private_decrypt( $input, $out, EnvConf::$rsaConf['rsa_private_key'] ))
                return "";
            $result.= $out;
            $index += EnvConf::$rsaConf['max_de_length'];
            $input = substr($source, $index, EnvConf::$rsaConf['max_de_length']);
        }
        return $result;	
	}

	public static function BRDecode($source)
	{
		$mid = base64_decode($source);
		$result = self::rsaDecode($mid);
		return $result;
	}
	
	public static function RBEncode($source)
	{
		$mid = self::rsaEncode($source);
		$result = base64_encode($mid);
		return $result;
	}
}

//var_dump(RSAEncoder::RBEncode('wu_jing@baidu.com'));
//var_dump(RSAEncoder::BRDecode(RSAEncoder::RBEncode('wu_jing@baidu.com')));

?>
