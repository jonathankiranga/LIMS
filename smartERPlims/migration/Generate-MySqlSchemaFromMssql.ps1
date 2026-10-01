param(
    [Parameter(Mandatory = $true)][string]$Server,
    [Parameter(Mandatory = $true)][string]$Database,
    [Parameter(Mandatory = $true)][string]$User,
    [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Password,
    [string]$OutputFile = ".\migration\mozillaerpv2.mysql.schema.sql"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Quote-MySqlId {
    param([string]$Name)
    return ('`' + $Name.Replace('`', '``') + '`')
}

function Normalize-SqlDefault {
    param([string]$Definition)

    if ([string]::IsNullOrWhiteSpace($Definition)) {
        return $null
    }

    $d = $Definition.Trim()

    # SQL Server often stores defaults wrapped in nested parentheses.
    while ($d.StartsWith("(") -and $d.EndsWith(")")) {
        $inner = $d.Substring(1, $d.Length - 2).Trim()
        if ($inner.Length -eq 0) { break }
        $d = $inner
    }

    # N'unicode' -> 'unicode'
    if ($d -match "^N'(.*)'$") {
        $d = "'" + $Matches[1] + "'"
    }

    switch -Regex ($d.ToUpperInvariant()) {
        "^(GETDATE\(\)|SYSDATETIME\(\)|CURRENT_TIMESTAMP)$" { return "CURRENT_TIMESTAMP" }
        "^(NEWID\(\))$" { return "(UUID())" }
        default { return $d }
    }
}

function Convert-MssqlTypeToMySql {
    param(
        [string]$TypeName,
        [int]$MaxLength,
        [int]$Precision,
        [int]$Scale,
        [bool]$IsIdentity
    )

    $t = $TypeName.ToLowerInvariant()
    switch ($t) {
        "bigint" { return "BIGINT" }
        "int" { return "INT" }
        "smallint" { return "SMALLINT" }
        "tinyint" { return "TINYINT" }
        "bit" { return "TINYINT(1)" }
        "decimal" { return "DECIMAL($Precision,$Scale)" }
        "numeric" { return "DECIMAL($Precision,$Scale)" }
        "money" { return "DECIMAL(19,4)" }
        "smallmoney" { return "DECIMAL(10,4)" }
        "float" { return "DOUBLE" }
        "real" { return "FLOAT" }
        "date" { return "DATE" }
        "datetime" { return "DATETIME" }
        "datetime2" { return "DATETIME" }
        "smalldatetime" { return "DATETIME" }
        "time" { return "TIME" }
        "datetimeoffset" { return "DATETIME" }
        "char" {
            if ($MaxLength -lt 0) { return "CHAR(255)" }
            return "CHAR($MaxLength)"
        }
        "nchar" {
            if ($MaxLength -lt 0) { return "CHAR(255)" }
            return "CHAR(" + [Math]::Max([int]($MaxLength / 2), 1) + ")"
        }
        "varchar" {
            if ($MaxLength -eq -1) { return "LONGTEXT" }
            return "VARCHAR($MaxLength)"
        }
        "nvarchar" {
            if ($MaxLength -eq -1) { return "LONGTEXT" }
            return "VARCHAR(" + [Math]::Max([int]($MaxLength / 2), 1) + ")"
        }
        "text" { return "LONGTEXT" }
        "ntext" { return "LONGTEXT" }
        "binary" {
            if ($MaxLength -lt 0) { return "VARBINARY(255)" }
            return "BINARY($MaxLength)"
        }
        "varbinary" {
            if ($MaxLength -eq -1) { return "LONGBLOB" }
            return "VARBINARY($MaxLength)"
        }
        "image" { return "LONGBLOB" }
        "uniqueidentifier" { return "CHAR(36)" }
        "xml" { return "LONGTEXT" }
        "timestamp" { return "BINARY(8)" } # SQL Server rowversion/timestamp
        "rowversion" { return "BINARY(8)" }
        default { return "LONGTEXT" }
    }
}

function Invoke-SqlRows {
    param(
        [System.Data.SqlClient.SqlConnection]$Connection,
        [string]$Query,
        [hashtable]$Params = @{}
    )

    $cmd = $Connection.CreateCommand()
    $cmd.CommandText = $Query
    foreach ($k in $Params.Keys) {
        $p = $cmd.Parameters.Add("@$k", [System.Data.SqlDbType]::NVarChar, 4000)
        $p.Value = [string]$Params[$k]
    }

    $da = New-Object System.Data.SqlClient.SqlDataAdapter $cmd
    $dt = New-Object System.Data.DataTable
    [void]$da.Fill($dt)
    return ,$dt
}

$connStr = "Server=$Server;Database=$Database;User ID=$User;Password=$Password;Encrypt=False;TrustServerCertificate=True;"
$conn = New-Object System.Data.SqlClient.SqlConnection $connStr
$conn.Open()

try {
    $tables = Invoke-SqlRows -Connection $conn -Query @"
SELECT
    s.name AS SchemaName,
    t.name AS TableName,
    t.object_id AS ObjectId
FROM sys.tables t
INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
WHERE t.is_ms_shipped = 0
ORDER BY s.name, t.name;
"@

    $lines = New-Object System.Collections.Generic.List[string]
    $lines.Add("-- Auto-generated from SQL Server database [$Database]")
    $lines.Add("-- Source server: $Server")
    $lines.Add("-- Generated on: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")")
    $lines.Add("SET NAMES utf8mb4;")
    $lines.Add("SET FOREIGN_KEY_CHECKS=0;")
    $lines.Add("CREATE DATABASE IF NOT EXISTS $(Quote-MySqlId $Database) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;")
    $lines.Add("USE $(Quote-MySqlId $Database);")
    $lines.Add("")

    $fkLines = New-Object System.Collections.Generic.List[string]
    $indexLines = New-Object System.Collections.Generic.List[string]

    foreach ($t in $tables.Rows) {
        $schemaName = [string]$t.SchemaName
        $tableName = [string]$t.TableName
        $objectId = [int]$t.ObjectId
        $mysqlTable = Quote-MySqlId $tableName

        $cols = Invoke-SqlRows -Connection $conn -Query @"
SELECT
    c.column_id AS ColumnId,
    c.name AS ColumnName,
    ty.name AS TypeName,
    c.max_length AS MaxLength,
    c.precision AS Prec,
    c.scale AS ScaleVal,
    c.is_nullable AS IsNullable,
    c.is_identity AS IsIdentity,
    c.is_computed AS IsComputed,
    dc.definition AS DefaultDefinition
FROM sys.columns c
INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
LEFT JOIN sys.default_constraints dc ON c.default_object_id = dc.object_id
WHERE c.object_id = @ObjectId
ORDER BY c.column_id;
"@ -Params @{ ObjectId = $objectId }

        $pk = Invoke-SqlRows -Connection $conn -Query @"
SELECT c.name AS ColumnName, ic.key_ordinal
FROM sys.key_constraints kc
INNER JOIN sys.index_columns ic ON kc.parent_object_id = ic.object_id AND kc.unique_index_id = ic.index_id
INNER JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
WHERE kc.parent_object_id = @ObjectId AND kc.type = 'PK'
ORDER BY ic.key_ordinal;
"@ -Params @{ ObjectId = $objectId }

        $uqIndexes = Invoke-SqlRows -Connection $conn -Query @"
SELECT
    i.name AS IndexName,
    c.name AS ColumnName,
    ic.key_ordinal AS KeyOrdinal
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
WHERE i.object_id = @ObjectId
  AND i.is_unique = 1
  AND i.is_primary_key = 0
  AND i.is_unique_constraint = 0
ORDER BY i.name, ic.key_ordinal;
"@ -Params @{ ObjectId = $objectId }

        $fks = Invoke-SqlRows -Connection $conn -Query @"
SELECT
    fk.name AS ForeignKeyName,
    pc.name AS ParentColumn,
    rt.name AS RefTable,
    rc.name AS RefColumn,
    fkc.constraint_column_id AS Ordinal,
    fk.delete_referential_action_desc AS OnDeleteAction,
    fk.update_referential_action_desc AS OnUpdateAction
FROM sys.foreign_keys fk
INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
INNER JOIN sys.columns pc ON pc.object_id = fkc.parent_object_id AND pc.column_id = fkc.parent_column_id
INNER JOIN sys.tables rt ON rt.object_id = fkc.referenced_object_id
INNER JOIN sys.columns rc ON rc.object_id = fkc.referenced_object_id AND rc.column_id = fkc.referenced_column_id
WHERE fk.parent_object_id = @ObjectId
ORDER BY fk.name, fkc.constraint_column_id;
"@ -Params @{ ObjectId = $objectId }

        $lines.Add("DROP TABLE IF EXISTS $mysqlTable;")
        $lines.Add("CREATE TABLE $mysqlTable (")

        $colDefs = New-Object System.Collections.Generic.List[string]
        foreach ($c in $cols.Rows) {
            $colName = [string]$c.ColumnName
            $typeName = [string]$c.TypeName
            $maxLen = [int]$c.MaxLength
            $prec = [int]$c.Prec
            $scale = [int]$c.ScaleVal
            $isNullable = [int]$c.IsNullable
            $isIdentity = [int]$c.IsIdentity
            $isComputed = [int]$c.IsComputed
            $defaultDef = if ($null -eq $c.DefaultDefinition) { $null } else { [string]$c.DefaultDefinition }

            $mysqlType = Convert-MssqlTypeToMySql -TypeName $typeName -MaxLength $maxLen -Precision $prec -Scale $scale -IsIdentity:([bool]$isIdentity)
            $nullSql = if ($isNullable -eq 1) { "NULL" } else { "NOT NULL" }
            $extra = ""
            if ($isIdentity -eq 1) {
                $extra = " AUTO_INCREMENT"
            }

            if ($isComputed -eq 1) {
                $colDefs.Add("  $(Quote-MySqlId $colName) $mysqlType NULL /* computed column in MSSQL */")
                continue
            }

            $defaultSql = ""
            $normalizedDefault = Normalize-SqlDefault -Definition $defaultDef
            if ($normalizedDefault) {
                $defaultSql = " DEFAULT $normalizedDefault"
            }

            $colDefs.Add("  $(Quote-MySqlId $colName) $mysqlType $nullSql$defaultSql$extra")
        }

        if ($pk.Rows.Count -gt 0) {
            $pkCols = @()
            foreach ($p in $pk.Rows) {
                $pkCols += (Quote-MySqlId ([string]$p.ColumnName))
            }
            $colDefs.Add("  PRIMARY KEY (" + ($pkCols -join ", ") + ")")
        }

        $lines.Add(($colDefs -join ",`n"))
        $lines.Add(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;")
        $lines.Add("")

        if ($uqIndexes.Rows.Count -gt 0) {
            $indexGroups = $uqIndexes | Group-Object IndexName
            foreach ($g in $indexGroups) {
                $idxCols = $g.Group | Sort-Object KeyOrdinal | ForEach-Object { Quote-MySqlId ([string]$_.ColumnName) }
                $indexLines.Add("ALTER TABLE $mysqlTable ADD UNIQUE KEY $(Quote-MySqlId ([string]$g.Name)) (" + ($idxCols -join ", ") + ");")
            }
        }

        if ($fks.Rows.Count -gt 0) {
            $fkGroups = $fks | Group-Object ForeignKeyName
            foreach ($g in $fkGroups) {
                $ordered = @($g.Group | Sort-Object Ordinal)
                $parentCols = $ordered | ForEach-Object { Quote-MySqlId ([string]$_.ParentColumn) }
                $refCols = $ordered | ForEach-Object { Quote-MySqlId ([string]$_.RefColumn) }
                $refTable = Quote-MySqlId ([string]$ordered[0].RefTable)

                $onDelete = ""
                $onUpdate = ""

                $del = [string]$ordered[0].OnDeleteAction
                $upd = [string]$ordered[0].OnUpdateAction
                if ($del -eq "CASCADE") { $onDelete = " ON DELETE CASCADE" }
                elseif ($del -eq "SET_NULL") { $onDelete = " ON DELETE SET NULL" }
                elseif ($del -eq "SET_DEFAULT") { $onDelete = " ON DELETE SET DEFAULT" }

                if ($upd -eq "CASCADE") { $onUpdate = " ON UPDATE CASCADE" }
                elseif ($upd -eq "SET_NULL") { $onUpdate = " ON UPDATE SET NULL" }
                elseif ($upd -eq "SET_DEFAULT") { $onUpdate = " ON UPDATE SET DEFAULT" }

                $fkLines.Add(
                    "ALTER TABLE $mysqlTable ADD CONSTRAINT $(Quote-MySqlId ([string]$g.Name)) FOREIGN KEY (" +
                    ($parentCols -join ", ") + ") REFERENCES $refTable (" + ($refCols -join ", ") + ")$onDelete$onUpdate;"
                )
            }
        }
    }

    if ($indexLines.Count -gt 0) {
        $lines.Add("-- Unique indexes")
        foreach ($l in $indexLines) { $lines.Add($l) }
        $lines.Add("")
    }

    if ($fkLines.Count -gt 0) {
        $lines.Add("-- Foreign keys")
        foreach ($l in $fkLines) { $lines.Add($l) }
        $lines.Add("")
    }

    $lines.Add("SET FOREIGN_KEY_CHECKS=1;")

    $outDir = Split-Path -Parent $OutputFile
    if ($outDir -and -not (Test-Path $outDir)) {
        New-Item -ItemType Directory -Path $outDir | Out-Null
    }

    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllLines($OutputFile, $lines, $utf8NoBom)

    Write-Host "Generated MySQL schema script: $OutputFile"
    Write-Host "Tables exported: $($tables.Rows.Count)"
}
finally {
    if ($conn.State -ne [System.Data.ConnectionState]::Closed) {
        $conn.Close()
    }
}
