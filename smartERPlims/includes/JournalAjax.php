<?php
/* Journal drill-down — PHP 7.0+ / MySQL (mysqli) version
 * Converted from MS-SQL ODBC original.
 */

define('LIKE', 'LIKE');

session_write_close();
session_name('ErpWithCRM');
session_start();

include('../config.php');
include('DateFunctions.inc');

global $db;

// ---------------------------------------------------------------------------
// Database connection — mysqli replaces odbc_connect
// ---------------------------------------------------------------------------
$database = $_SESSION['DatabaseName'];
$db = mysqli_connect($host, $DBUser, $DBPassword, $database);

if (!$db) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($db, 'utf8');

// ---------------------------------------------------------------------------
// DB wrapper functions  (drop-in replacements for the odbc_* originals)
// ---------------------------------------------------------------------------

/**
 * Execute a SQL statement and return the result.
 *
 * @param  string        $SQL
 * @param  mysqli        $Conn
 * @return mysqli_result|bool
 */
function DB_query($SQL, $Conn)
{
    $result = mysqli_query($Conn, $SQL);
    if ($result === false) {
        trigger_error('DB_query failed: ' . mysqli_error($Conn) . ' | SQL: ' . $SQL, E_USER_WARNING);
    }
    return $result;
}

/**
 * Fetch the next row as an associative array.
 *
 * @param  mysqli_result $ResultIndex
 * @return array|null
 */
function DB_fetch_array($ResultIndex)
{
    return mysqli_fetch_assoc($ResultIndex);
}

/**
 * Return the number of rows in a result set.
 *
 * @param  mysqli_result $ResultIndex
 * @return int
 */
function DB_num_rows($ResultIndex)
{
    return mysqli_num_rows($ResultIndex);
}

/**
 * Fetch the next row as a zero-indexed array.
 * Mirrors the original odbc_fetch_row / odbc_result loop.
 *
 * @param  mysqli_result $ResultIndex
 * @return array
 */
function DB_fetch_row($ResultIndex)
{
    $row = mysqli_fetch_row($ResultIndex);
    return $row ? $row : array();
}

// ---------------------------------------------------------------------------
// Company record — loaded into session on every request
// ---------------------------------------------------------------------------

$sql = "SELECT coycode, coyname, PIN, vat,
               regoffice1, regoffice2, regoffice3, regoffice4, regoffice5, regoffice6,
               telephone, fax, email, currencydefault, currencies.decimalplaces
        FROM   companies
        INNER JOIN currencies ON companies.currencydefault = currencies.currabrev
        WHERE  coycode = 1";

$ReadCoyResult = DB_query($sql, $db);
if (DB_num_rows($ReadCoyResult) > 0) {
    $_SESSION['CompanyRecord'] = DB_fetch_array($ReadCoyResult);
}

// ---------------------------------------------------------------------------
// System config — loaded into session on every request
// ---------------------------------------------------------------------------

$ConfigResult = DB_query("SELECT confname, confvalue FROM config", $db);
while ($myrow = DB_fetch_array($ConfigResult)) {
    if (is_numeric($myrow['confvalue'])
        && $myrow['confname'] !== 'DefaultPriceList'
        && $myrow['confname'] !== 'VersionNumber'
    ) {
        $_SESSION[$myrow['confname']] = (double)$myrow['confvalue'];
    } else {
        $_SESSION[$myrow['confname']] = $myrow['confvalue'];
    }
}

// ---------------------------------------------------------------------------
// Helper: return account description for a given account code
// ---------------------------------------------------------------------------

/**
 * @param  string $code  Account number from acct table
 * @return string        Account description, or empty string if not found
 */
function getaccount($code)
{
    global $db;
    $code   = mysqli_real_escape_string($db, $code);
    $result = DB_query("SELECT accdesc FROM acct WHERE accno = '$code'", $db);
    $rows   = DB_fetch_row($result);
    return isset($rows[0]) ? trim($rows[0]) : '';
}

// ---------------------------------------------------------------------------
// Document type lookup — indexed by typeid
// ---------------------------------------------------------------------------

$Doctypes = array();
$Results  = DB_query("SELECT typeid, typename FROM systypes_1", $db);
while ($roe = DB_fetch_array($Results)) {
    $Doctypes[$roe['typeid']] = $roe['typename'];
}

// ---------------------------------------------------------------------------
// Journal drill-down query — date range + optional document number filter
// ---------------------------------------------------------------------------

$journalDate = FormatDateForSQL($_POST['journaldate']);
$journalFind = isset($_POST['journalfind'])
    ? mysqli_real_escape_string($db, $_POST['journalfind'])
    : '';

$getsql = "SELECT
               rowid,
               journalno,
               Docdate,
               period,
               DocumentNo,
               DocumentType,
               accountcode,
               balaccountcode,
               (amount * ExchangeRate)  AS AMOUNT,
               currencycode,
               ExchangeRate,
               cutomercode,
               suppliercode,
               bankcode,
               reconcilled,
               narration,
               ExchangeRateDiff,
               VATaccountcode,
               VATamount,
               dimension,
               dimension2
           FROM Generalledger
           WHERE Docdate BETWEEN '$journalDate' AND '$journalDate'";

if (mb_strlen($journalFind) > 0) {
    $getsql .= " AND DocumentNo = '$journalFind'";
}

$getsql .= " ORDER BY Docdate, rowid ASC";

// ---------------------------------------------------------------------------
// Build HTML output table
// ---------------------------------------------------------------------------

$object = '<table class="statement display"><thead><tr>'
        . '<th>Date</th><th>Doc No</th><th>Doc Type</th>'
        . '<th>Debit Account</th><th>Credit Account</th>'
        . '<th>Narrative</th><th>Project</th><th>AMOUNT</th>'
        . '</tr></thead><tbody>';

$ResultDrill = DB_query($getsql, $db);

while ($row = DB_fetch_array($ResultDrill)) {

    if ($row['AMOUNT'] != 0) {

        $debitAcct  = getaccount($row['balaccountcode']);
        $creditAcct = getaccount($row['accountcode']);

        $docType = isset($Doctypes[$row['DocumentType']])
            ? $Doctypes[$row['DocumentType']]
            : '';

        $object .= '<tr>';
        $object .= '<td>' . ConvertSQLDate($row['Docdate']) . '</td>';
        $object .= sprintf(
            '<td><a href="?DocumentNo=%s&Docdate=%s">%s</a></td>',
            htmlspecialchars($row['DocumentNo'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($row['Docdate'],    ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($row['DocumentNo'], ENT_QUOTES, 'UTF-8')
        );
        $object .= '<td>' . htmlspecialchars($docType,              ENT_QUOTES, 'UTF-8') . '</td>';
        $object .= '<td>' . htmlspecialchars($debitAcct,            ENT_QUOTES, 'UTF-8') . '</td>';
        $object .= '<td>' . htmlspecialchars($creditAcct,           ENT_QUOTES, 'UTF-8') . '</td>';
        $object .= '<td>' . htmlspecialchars($row['narration'],     ENT_QUOTES, 'UTF-8') . '</td>';
        $object .= '<td>' . htmlspecialchars($row['dimension2'],    ENT_QUOTES, 'UTF-8') . '</td>';
        $object .= '<td class="number">' . number_format($row['AMOUNT'], 2) . '</td>';
        $object .= '</tr>';
    }
}

$object .= '</tbody>'
         . '<tfoot><tr>'
         . '<th>Date</th><th>Doc No</th><th>Doc Type</th>'
         . '<th>Debit Account</th><th>Credit Account</th>'
         . '<th>Narrative</th><th>Project</th><th>Amount</th>'
         . '</tr></tfoot></table>';

echo $object;

 class AjaxUnsafeCrypto{
    const METHOD = 'aes-256-ctr';

    /**
     * Encrypts (but does not authenticate) a message
     * 
     * @param string $message - plaintext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encode - set to TRUE to return a base64-encoded 
     * @return string (raw binary)
     */
    public static function encrypt($message, $key, $encode = false)
    {
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }

    /**
     * Decrypts (but does not verify) a message
     * 
     * @param string $message - ciphertext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encoded - are we expecting an encoded string?
     * @return string
     */
    public static function decrypt($message, $key, $encoded = false)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        return $plaintext;
    }
}
 
?>
