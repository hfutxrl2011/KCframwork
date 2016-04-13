<?php

require_once __DIR__ . '/IDGen.class.php';

$provider = 'idgen';
$magicNum = 13579;
$server = array(array('host' => '127.0.0.1', 'port' => 8063));
$requestId = 1234567890;
$tmp = new IDGen_IDGen($provider, $magicNum, $server, $requestId);
#getID合法的参数请参考/home/work/wu_jing/cproject_common/idgen-master/src/conf/idgen.conf的idconf.types选项
if($id = $tmp->getID('uid')){
	printf("GET UID SUCC [ uid: %s, detail string: %s, detail_array: %s ].\n", $id, $tmp->getResDetailAsString(), json_encode($tmp->getResDetail()));
}else{
	printf("GET UID FAILED [ error_code: %s, error info: %s, detail string: %s ]\n", $tmp->getErrorCode(), $tmp->getErrorInfo(), $tmp->getResDetailAsString());
}
printf("\n");
if($id = $tmp->getID('child_id')){
	printf("GET CHILD_ID SUCC [ child_id: %s, detail string: %s, detail_array: %s ].\n", $id, $tmp->getResDetailAsString(), json_encode($tmp->getResDetail()));
}else{
	printf("GET CHILD_ID FAILED [ error_code: %s, error info: %s, detail string: %s ]\n", $tmp->getErrorCode(), $tmp->getErrorInfo(), $tmp->getResDetailAsString());
}
printf("\n");
if($id = $tmp->getID('child_idX')){
	printf("GET CHILD_IDX SUCC [ child_id: %s, detail string: %s, detail_array: %s ].\n", $id, $tmp->getResDetailAsString(), json_encode($tmp->getResDetail()));
}else{
	printf("GET CHILD_IDX FAILED [ error_code: %s, error info: %s, detail string: %s ]\n", $tmp->getErrorCode(), $tmp->getErrorInfo(), $tmp->getResDetailAsString());
}
printf("\n");
?>
