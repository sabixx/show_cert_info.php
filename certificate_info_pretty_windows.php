<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function get_certificate_data_from_url($url) {
    if (!$url) {
        throw new Exception("URL is empty.");
    }

    // Create a stream context to get the certificate
    $context = stream_context_create(["ssl" => ["capture_peer_cert" => TRUE]]);
    $stream = @stream_socket_client("ssl://$url:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    if (!$stream) {
        throw new Exception("Unable to connect to URL: $url. Error: $errstr");
    }

    $params = stream_context_get_params($stream);
    $cert_resource = $params['options']['ssl']['peer_certificate'];
    $cert_content = openssl_x509_export($cert_resource, $cert_content) ? $cert_content : false;

    if ($cert_content === false) {
        throw new Exception("Unable to export certificate from URL: $url");
    }

    $cert_info = openssl_x509_parse($cert_content);
    if ($cert_info === false) {
        throw new Exception("Unable to parse certificate from URL: $url");
    }

    $valid_from = date_create(date('Y-m-d H:i:s', $cert_info['validFrom_time_t']));
    $valid_to = date_create(date('Y-m-d H:i:s', $cert_info['validTo_time_t']));
    $validity_period = date_diff($valid_from, $valid_to);

    $data = [
        'Serial Number' => $cert_info['serialNumber'],
        'Subject' => $cert_info['subject']['CN'],
        'Issuer' => $cert_info['issuer']['CN'],
        'Valid From' => date('Y-m-d H:i:s', $cert_info['validFrom_time_t']),
        'Valid To' => date('Y-m-d H:i:s', $cert_info['validTo_time_t']),
        'Validity Period' => $validity_period->format('%y years, %m months, %d days'),
        'Signature Algorithm' => $cert_info['signatureTypeSN'],
        'Thumbprint' => strtoupper(sha1($cert_content)),
    ];

    if (isset($cert_info['extensions']['subjectAltName'])) {
        $data['Subject Alternative Names'] = $cert_info['extensions']['subjectAltName'];
    }

    return $data;
}

$url = $_SERVER['HTTP_HOST'];

try {
    $cert_data = get_certificate_data_from_url($url);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Certificate Information</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Mulish:wght@400;600&display=swap');
        body {
            font-family: 'Mulish', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f8f8;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Certificate Information</h1>
        <p>URL: <?php echo htmlspecialchars($url); ?></p>
        <table>
            <?php foreach ($cert_data as $key => $value): ?>
                <tr>
                    <th><?php echo htmlspecialchars($key); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
