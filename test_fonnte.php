<?php

$token = 'zGoLDZaEeQugjkQwbuTg';
$target = '088231451295';

$message = "Halo dari Rosa Indah Printing 👋\n\n"
    . "Ini adalah pesan percobaan WhatsApp dari sistem website.\n\n"
    . "Jika pesan ini masuk, berarti koneksi Fonnte berhasil.";

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.fonnte.com/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'target' => $target,
        'message' => $message,
        'countryCode' => '62'
    ],
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $token
    ],
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo 'ERROR CURL: ' . curl_error($curl);
    curl_close($curl);
    exit;
}

curl_close($curl);

echo '<pre>';
print_r(json_decode($response, true));
echo '</pre>';
