<?php
$c=stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>'{"email":"test101@test.com"}','ignore_errors'=>true]]);
$res = file_get_contents('http://localhost:8000/api/auth/register/options',false,$c);
var_dump($res);
var_dump($http_response_header);
