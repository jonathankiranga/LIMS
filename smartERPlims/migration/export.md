.\migration\Export-MssqlDataToMySql.ps1 `
  -MssqlServer "(local)" `
  -MssqlDatabase "mozillaerpv2" `
  -MssqlUser "sa" `
  -MssqlPassword "v3ga2019" `
  -MySqlHost "localhost" `
  -MySqlDatabase "mozillaerpv2" `
  -MySqlUser "root" `
  -MySqlPassword "mysqlpassword" `
  -OutputDir ".\migration\export"


mysql -h localhost -u root -p < "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\mozillaerpv2.mysql.schema.sql"


mysql -h localhost -u root -p mozillaerpv2 < "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\export\02_mozillaerpv2_data.mysql.sql"


powershell -NoProfile -ExecutionPolicy Bypass -File "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\Generate-MySqlSchemaFromMssql.ps1" -Server "(local)" -Database "mozillaerpv2" -User "sa" -Password "v3ga2019" -OutputFile "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\mozillaerpv2.mysql.schema.sql"


powershell -NoProfile -ExecutionPolicy Bypass -File "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\Export-MssqlDataToMySql.ps1" -MssqlServer "(local)" -MssqlDatabase "mozillaerpv2" -MssqlUser "sa" -MssqlPassword "v3ga2019" -MySqlHost "localhost" -MySqlDatabase "mozillaerpv2" -MySqlUser "root" -MySqlPassword "mysqlpassword" -OutputDir "D:\inetpub\wwwroot\smartERPmysql.2.2\migration\export"


