<?php
// counter.php — incrementa e restituisce il contatore in visits.json

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$file = __DIR__ . '/visits.json';

// assicura che il file esista
if (!file_exists($file)) {
  file_put_contents($file, json_encode(['total' => 0], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

// tenta lock per evitare race in accessi simultanei
$fp = fopen($file, 'c+');
if (!$fp) {
  http_response_code(500);
  echo json_encode(['error' => 'cannot open file']);
  exit;
}

if (!flock($fp, LOCK_EX)) {
  fclose($fp);
  http_response_code(500);
  echo json_encode(['error' => 'lock failed']);
  exit;
}

// leggi stato attuale
$size = filesize($file);
if ($size > 0) {
  rewind($fp);
  $raw  = fread($fp, $size);
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = ['total' => 0];
} else {
  $data = ['total' => 0];
}

// incrementa contatore assoluto
$data['total'] = (int)($data['total'] ?? 0) + 1;

// salva
rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

// risposta
echo json_encode($data, JSON_UNESCAPED_UNICODE);
