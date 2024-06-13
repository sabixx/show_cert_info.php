<?php
function get_certificate_data($cert_file) {
    $cert_content = file_get_contents($cert_file);
    $cert_info = openssl_x509_parse($cert_content);
    
    $data = [
        'Serial Number' => $cert_info['serialNumber'],
        'Subject' => $cert_info['subject']['CN'],
        'Issuer' => $cert_info['issuer']['CN'],
        'Valid From' => date('Y-m-d H:i:s', $cert_info['validFrom_time_t']),
        'Valid To' => date('Y-m-d H:i:s', $cert_info['validTo_time_t']),
        'Signature Algorithm' => $cert_info['signatureTypeSN'],
        'Thumbprint' => strtoupper(sha1($cert_content)),
    ];

    if (isset($cert_info['extensions']['subjectAltName'])) {
        $data['Subject Alternative Names'] = $cert_info['extensions']['subjectAltName'];
    }

    return $data;
}

$cert_file = '/etc/nginx/ssl/push_demo.crt';
$cert_data = get_certificate_data($cert_file);
?>

<!DOCTYPE html>
<html>
<head>
    <title>push tlsp.demo</title>
</head>
<body>
    <h1>Push Provisioning is awesome</h1>
    <table border="1">
        <?php foreach ($cert_data as $key => $value): ?>
            <tr>
                <th><?php echo htmlspecialchars($key); ?></th>
                <td><?php echo htmlspecialchars($value); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
