# Edit these credential values for your environment
$MssqlServer = "(local)"           # e.g., "DESKTOP-ABC\SQLEXPRESS" or "192.168.1.10"
$MssqlDatabase = "mozillaerp v2"       # e.g., "smartERP"
$MssqlUser = "sa"                             # MSSQL user
$MssqlPassword = "v3ga2019"                            # MSSQL password

$MySqlHost = "localhost"                      # MySQL host
$MySqlDatabase = "mozillaerpv2"       # e.g., "smartERP_mysql"
$MySqlUser = "root"                           # MySQL user
$MySqlPassword = "mysqlpassword"                            # MySQL password

# Optional: Set to $true to auto-import data to MySQL after export
$ApplyToMySql = $false

# Run the export script
& (Join-Path $PSScriptRoot "Export-MssqlDataToMySql.ps1") `
    -MssqlServer $MssqlServer `
    -MssqlDatabase $MssqlDatabase `
    -MssqlUser $MssqlUser `
    -MssqlPassword $MssqlPassword `
    -MySqlHost $MySqlHost `
    -MySqlDatabase $MySqlDatabase `
    -MySqlUser $MySqlUser `
    -MySqlPassword $MySqlPassword `
    -OutputDir (Join-Path $PSScriptRoot "export") `
    -ApplyToMySql:$ApplyToMySql `
    -Verbose

Write-Host "Export complete! Check ./export directory for results." -ForegroundColor Green
Read-Host "Press ENTER to exit"
