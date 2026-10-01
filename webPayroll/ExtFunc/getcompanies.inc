<?php
include('credentials.inc');
include('NTLMStream.php');
include('NTLMSoapClient.php');
// we unregister the current HTTP wrapper
stream_wrapper_unregister('http');
// we register the new HTTP wrapper
stream_wrapper_register('http','NTLMStream') or die("Failed to register protocol");
//$baseURL = 'http://localhost:7047/DynamicsNAV/WS/';
$service = 'SystemService';

try{
    $_SESSION['DynamicsNav71'] = new NTLMSoapClient(baseURL.'SystemService');
} catch (NTLMSoapClient $e) {
       var_dump($e);
}

// Find the first Company in the Companies
$result = $_SESSION['DynamicsNav71']->Companies();
$companies = $result->return_value;

echo '<form method="post" id="DynamicsNav" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<div><br />';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<table class="table-bordered"><tr>';


if(isset($_POST['company'])){

    $_SESSION['DynamicsNavpageURL'] = baseURL.rawurlencode($_POST['company']).'/Page/Customer';

    echo '<td><label>Selected Compay is :'.$_POST['company'] .'</label></td><tr/><tr>';
}

    echo '<td><select name="company">';

    if (is_array($companies)) {
    foreach($companies as $company) {
        echo "<option>". $company ."</option>";
      }
    } else {
        echo "<option>". $companies ."</option>";
    }
    echo '<select/></td>';

echo '</tr></table></div>
    <input type="submit" name="selectcompany" value="Select company"/></form>';

if(isset($_SESSION['DynamicsNavpageURL'])){

    $page = new NTLMSoapClient($_SESSION['DynamicsNavpageURL']);
    $params = array('No' => '10000');

    $result = $page->Read($params);
    $customer = $result->Customer;

    if (is_array($customer)) {
      foreach($customer as $cust) {
        echo $cust->Name."<br>";
      }
    } else {
      echo $customer->Name."<br>";
    }

}
// restore the original http protocole
stream_wrapper_restore('http');
?>
