<?php
// batepapo/signaling.php
// Servidor de sinalização WebRTC multiusuário
$file = __DIR__."/signals.json";
if(!file_exists($file)) file_put_contents($file, "{}");
$data = json_decode(file_get_contents($file), true);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $from = $_POST['from'];
  $to = $_POST['to'];
  $msg = $_POST['data'];
  $data["$from|$to"] = $msg;
  file_put_contents($file, json_encode($data));
  exit("ok");
}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
  $from = $_GET['from'];
  $responses = [];
  foreach($data as $key => $msg){
    list($src, $dst) = explode('|', $key);
    if($dst === $from){
      $responses[] = json_decode($msg, true);
      unset($data[$key]);
    }
  }
  file_put_contents($file, json_encode($data));
  header("Content-Type: application/json");
  echo json_encode($responses);
}
?>
