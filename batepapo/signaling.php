<?php
// Salva e recupera sinais de oferta/resposta entre A e B
$file = __DIR__."/signals.json";
if(!file_exists($file)) file_put_contents($file, "{}");
$data = json_decode(file_get_contents($file), true);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $from = $_POST['from']; $to = $_POST['to']; $msg = $_POST['data'];
  $data["$from-to-$to"] = $msg;
  file_put_contents($file, json_encode($data));
  exit("ok");
}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
  $from = $_GET['from']; $to = $_GET['to'];
  $key = "$to-to-$from"; // invertido
  if(isset($data[$key])){
    header("Content-Type: application/json");
    echo $data[$key];
    unset($data[$key]);
    file_put_contents($file, json_encode($data));
  }
}
?>
