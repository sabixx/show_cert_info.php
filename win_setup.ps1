# Downloads PHP and configures IIS to use PHP
#
# v001

# Define URLs and paths
$phpZipUrl = "https://windows.php.net/downloads/releases/php-8.3.10-nts-Win32-vs16-x64.zip"
$phpZipPath = "C:\php\php.zip"
$phpExtractPath = "C:\php"
$phpIniPath = "C:\php\php.ini"
$phpIniDevPath = "C:\php\php.ini-development"
$certInfoUrl = "https://raw.githubusercontent.com/sabixx/show_cert_info.php/main/certificate_info_pretty_windows.php"
$certInfoPath = "C:\inetpub\wwwroot\cert_info.php"
$phpCgiPath = Join-Path $phpExtractPath "php-cgi.exe"

# Create directories if not exist
New-Item -ItemType Directory -Force -Path $phpExtractPath

# Download PHP zip
Invoke-WebRequest -Uri $phpZipUrl -OutFile $phpZipPath

# Extract PHP zip
Expand-Archive -Path $phpZipPath -DestinationPath $phpExtractPath

# Remove zip file after extraction
Remove-Item -Path $phpZipPath

# Copy php.ini-development to php.ini
Copy-Item -Path $phpIniDevPath -Destination $phpIniPath

# Add extension=openssl to php.ini
Add-Content -Path $phpIniPath -Value "extension=openssl"

# Install CGI from IIS components
Import-Module ServerManager
Add-WindowsFeature Web-CGI

# Configure IIS Handler settings for PHP
Import-Module WebAdministration

# Add cert_info.php to the default document list for the entire IIS server
$defaultDocumentsPath = "MACHINE/WEBROOT/APPHOST"
Add-WebConfigurationProperty -pspath $defaultDocumentsPath -filter "system.webServer/defaultDocument/files" -name "." -value @{value="cert_info.php"}

# Add FastCGI application configuration for PHP
Add-WebConfiguration -PSPath 'MACHINE/WEBROOT/APPHOST' -Filter 'system.webServer/fastCgi' -Value @{ fullPath = $phpCgiPath; arguments = ""; maxInstances = 4; instanceMaxRequests = 10000 }

# Add PHP handler mapping
New-WebHandler -Name "PHP_via_FastCGI" -Path "*.php" -Verb "GET,HEAD,POST" -Modules "FastCgiModule" -ScriptProcessor $phpCgiPath -ResourceType "File"

# Ensure MIME type for PHP files is set
Set-WebConfigurationProperty -pspath 'MACHINE/WEBROOT/APPHOST' -filter "system.webServer/staticContent" -name "." -value @{fileExtension=".php";mimeType="application/x-httpd-php"}

# Download cert_info.php and place it in the web root
Invoke-WebRequest -Uri $certInfoUrl -OutFile $certInfoPath

# Restart IIS to apply changes
iisreset
 
