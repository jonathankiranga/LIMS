param(
    [Parameter(Mandatory = $true)][string]$MssqlServer,
    [Parameter(Mandatory = $true)][string]$MssqlDatabase,
    [Parameter(Mandatory = $true)][string]$MssqlUser,
    [Parameter(Mandatory = $true)][AllowEmptyString()][string]$MssqlPassword,

    [Parameter(Mandatory = $true)][string]$MySqlHost,
    [Parameter(Mandatory = $true)][string]$MySqlDatabase,
    [Parameter(Mandatory = $true)][string]$MySqlUser,
    [Parameter(Mandatory = $true)][AllowEmptyString()][string]$MySqlPassword,

    [string]$OutputDir = ".\migration\export",
    [int]$BatchSize = 500,
    [switch]$ApplyToMySql,
    [string]$MySqlExePath = "mysql"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Quote-MySqlId {
    param([Parameter(Mandatory = $true)][string]$Name)
    return ('`' + $Name.Replace('`', '``') + '`')
}

function Convert-ToMySqlLiteral {
    param([Parameter(ValueFromPipeline = $true)]$Value)

    if ($null -eq $Value -or $Value -is [System.DBNull]) {
        return "NULL"
    }

    if ($Value -is [bool]) {
        return ($(if ($Value) { "1" } else { "0" }))
    }

    if ($Value -is [byte[]]) {
        $hex = [System.BitConverter]::ToString($Value).Replace("-", "")
        return "X'$hex'"
    }

    # Handle DateTime objects
    if ($Value -is [DateTime]) {
        # Strip milliseconds and format as yyyy-MM-dd HH:mm:ss
        $v = [DateTime]$Value
        return "'" + $v.ToString("yyyy-MM-dd HH:mm:ss", [System.Globalization.CultureInfo]::InvariantCulture) + "'"
    }

    if ($Value -is [Guid]) {
        return "'" + $Value.ToString() + "'"
    }

    if ($Value -is [sbyte] -or
        $Value -is [byte] -or
        $Value -is [int16] -or
        $Value -is [uint16] -or
        $Value -is [int32] -or
        $Value -is [uint32] -or
        $Value -is [int64] -or
        $Value -is [uint64] -or
        $Value -is [single] -or
        $Value -is [double] -or
        $Value -is [decimal]) {
        return [Convert]::ToString($Value, [System.Globalization.CultureInfo]::InvariantCulture)
    }

    # Convert to string and escape
    $s = [string]$Value
    
    # Try to detect and convert date strings that weren't properly typed
    if ($s -match '^\d{1,2}/\d{1,2}/\d{4}' -or $s -match 'AM|PM') {
        try {
            $dt = [DateTime]::Parse($s, [System.Globalization.CultureInfo]::InvariantCulture)
            return "'" + $dt.ToString("yyyy-MM-dd HH:mm:ss", [System.Globalization.CultureInfo]::InvariantCulture) + "'"
        }
        catch {
            # If parsing fails, continue with string escaping
        }
    }
    
    $s = $s.Replace("\", "\\")
    $s = $s.Replace("'", "''")
    $s = $s.Replace([string][char]0, "")
    return "'" + $s + "'"
}

function Invoke-SqlRows {
    param(
        [Parameter(Mandatory = $true)][System.Data.SqlClient.SqlConnection]$Connection,
        [Parameter(Mandatory = $true)][string]$Query
    )

    $cmd = $Connection.CreateCommand()
    $cmd.CommandTimeout = 0
    $cmd.CommandText = $Query

    $da = New-Object System.Data.SqlClient.SqlDataAdapter $cmd
    $dt = New-Object System.Data.DataTable
    [void]$da.Fill($dt)
    return ,$dt
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir | Out-Null
}

$csvDir = Join-Path $OutputDir "csv"
if (-not (Test-Path $csvDir)) {
    New-Item -ItemType Directory -Path $csvDir | Out-Null
}

$dataSqlPath = Join-Path $OutputDir "02_mozillaerpv2_data.mysql.sql"
$manifestPath = Join-Path $OutputDir "export_manifest.csv"
$logPath = Join-Path $OutputDir "export.log"

$connStr = "Server=$MssqlServer;Database=$MssqlDatabase;User ID=$MssqlUser;Password=$MssqlPassword;Encrypt=False;TrustServerCertificate=True;"
$conn = New-Object System.Data.SqlClient.SqlConnection $connStr
$conn.Open()

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$sw = New-Object System.IO.StreamWriter($dataSqlPath, $false, $utf8NoBom)
$logSw = New-Object System.IO.StreamWriter($logPath, $false, $utf8NoBom)

$manifest = New-Object System.Collections.Generic.List[object]

try {
    $tables = Invoke-SqlRows -Connection $conn -Query @"
SELECT
    s.name AS SchemaName,
    t.name AS TableName
FROM sys.tables t
INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE t.is_ms_shipped = 0
ORDER BY s.name, t.name;
"@

    $sw.WriteLine("-- Auto-generated data export from MSSQL [$MssqlDatabase]")
    $sw.WriteLine("-- Source server: $MssqlServer")
    $sw.WriteLine("-- Generated on: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")")
    $sw.WriteLine("SET NAMES utf8mb4;")
    $sw.WriteLine("SET FOREIGN_KEY_CHECKS=0;")
    $sw.WriteLine("START TRANSACTION;")
    $sw.WriteLine("")

    $logSw.WriteLine("Export started: $(Get-Date -Format s)")

    foreach ($t in $tables.Rows) {
        $schema = [string]$t.SchemaName
        $table = [string]$t.TableName
        $qualified = "[" + $schema.Replace("]", "]]") + "].[" + $table.Replace("]", "]]") + "]"
        $mysqlTable = Quote-MySqlId $table
        $csvPath = Join-Path $csvDir ($schema + "." + $table + ".csv")

        $data = Invoke-SqlRows -Connection $conn -Query ("SELECT * FROM " + $qualified + ";")
        $rowCount = $data.Rows.Count

        # CSV backup per table.
        $objects = foreach ($r in $data.Rows) {
            $o = [ordered]@{}
            foreach ($c in $data.Columns) {
                $o[$c.ColumnName] = $r[$c.ColumnName]
            }
            [pscustomobject]$o
        }
        $objects | Export-Csv -Path $csvPath -NoTypeInformation -Encoding UTF8

        $manifest.Add([pscustomobject]@{
            SchemaName = $schema
            TableName  = $table
            Rows       = $rowCount
            CsvPath    = $csvPath
        })

        $logSw.WriteLine(("Exported {0}.{1} rows={2}" -f $schema, $table, $rowCount))

        if ($rowCount -eq 0) {
            continue
        }

        $columnNames = @()
        foreach ($c in $data.Columns) {
            $colName = $c.ColumnName.ToString()
            $columnNames += (Quote-MySqlId $colName)
        }
        $columnSql = $columnNames -join ", "

        $buffer = New-Object System.Collections.Generic.List[string]
        $i = 0
        $newlineSeparator = "," + [Environment]::NewLine
        
        foreach ($r in $data.Rows) {
            $vals = @()
            foreach ($c in $data.Columns) {
                $vals += (Convert-ToMySqlLiteral $r[$c.ColumnName])
            }
            $buffer.Add("(" + ($vals -join ", ") + ")")
            $i++

            if ($buffer.Count -ge $BatchSize) {
                $sw.WriteLine("INSERT INTO $mysqlTable ($columnSql) VALUES")
                $sw.WriteLine(($buffer -join $newlineSeparator) + ";")
                $sw.WriteLine("")
                $buffer.Clear()
            }
        }

        if ($buffer.Count -gt 0) {
            $sw.WriteLine("INSERT INTO $mysqlTable ($columnSql) VALUES")
            $sw.WriteLine(($buffer -join $newlineSeparator) + ";")
            $sw.WriteLine("")
            $buffer.Clear()
        }
    }

    $sw.WriteLine("COMMIT;")
    $sw.WriteLine("SET FOREIGN_KEY_CHECKS=1;")
}
finally {
    $sw.Flush()
    $sw.Close()
    $logSw.Flush()
    $logSw.Close()
    if ($conn.State -ne [System.Data.ConnectionState]::Closed) {
        $conn.Close()
    }
}

$manifest | Sort-Object SchemaName, TableName | Export-Csv -Path $manifestPath -NoTypeInformation -Encoding UTF8
# append completion after export loop finished
$logSw2 = New-Object System.IO.StreamWriter($logPath, $true, $utf8NoBom)
$logSw2.WriteLine("Export completed: $(Get-Date -Format s)")
$logSw2.Flush()
$logSw2.Close()

Write-Host "Data SQL: $dataSqlPath"
Write-Host "Manifest: $manifestPath"
Write-Host "CSV backup dir: $csvDir"
Write-Host "Tables exported: $($manifest.Count)"
Write-Host "Total rows: $((($manifest | Measure-Object -Property Rows -Sum).Sum))"

if ($ApplyToMySql) {
    $mysqlCmd = Get-Command $MySqlExePath -ErrorAction SilentlyContinue
    if ($null -eq $mysqlCmd) {
        throw "mysql client not found. Install MySQL client or pass -MySqlExePath to mysql.exe."
    }

    $resolvedMySqlExe = $mysqlCmd.Source
    $args = @(
        "-h", $MySqlHost,
        "-u", $MySqlUser,
        "-p$MySqlPassword",
        $MySqlDatabase,
        "-e", ("source " + $dataSqlPath.Replace("\", "/"))
    )

    & $resolvedMySqlExe @args
    if ($LASTEXITCODE -ne 0) {
        throw "mysql import failed with exit code $LASTEXITCODE"
    }

    Write-Host "MySQL import completed successfully."
}
