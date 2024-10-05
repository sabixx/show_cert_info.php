<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to get the certificate data
function get_certificate_data($cert_file) {
    if (!$cert_file) {
        throw new Exception("Certificate file path is empty.");
    }

    $cert_content = file_get_contents($cert_file);
    if ($cert_content === false) {
        throw new Exception("Unable to read certificate file: $cert_file");
    }

    $cert_info = openssl_x509_parse($cert_content);
    if ($cert_info === false) {
        throw new Exception("Unable to parse certificate: $cert_file");
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
    ];

    if (isset($cert_info['extensions']['subjectAltName'])) {
        $data['Subject Alternative Names'] = $cert_info['extensions']['subjectAltName'];
    }

    return $data;
}

// Dynamically get the port from the server
$port = $_SERVER['SERVER_PORT'];

// Construct the certificate path based on the port number
$cert_file = '/etc/nginx/ssl/push_demo.crt';

try {
    $cert_data = get_certificate_data($cert_file);
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
            position: relative;
        }
        .container {
            width: 80%;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
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
        .logo {
            position: absolute;
            max-width: 225px;
            max-height: 225px;
        }
        .logo.top-left {
            top: 10px;
            left: 10px;
        }
        .logo.top-right {
            top: 10px;
            right: 10px;
        }
        .logo.bottom-left {
            bottom: 10px;
            left: 10px;
        }
        .logo.bottom-right {
            bottom: 10px;
            right: 10px;
        }
        .c-text {
            color: #ffffff; 
            display: flex;
            justify-content: center;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="Venafi_CYBR_logo_R.svg" alt="Venafi CYBR Logo" class="logo top-right">
        <h1>Certificate Information</h1>
        <p>Certificate File: <?php echo htmlspecialchars($cert_file); ?></p>
        <table>
            <?php foreach ($cert_data as $key => $value): ?>
                <tr>
                    <th><?php echo htmlspecialchars($key); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="c-text">(C) 2024 CyberArk jens.sabitzer@cyberark.com</div>
</body>
</html>
