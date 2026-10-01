<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Registration</title>
    <link href="css/sampleregistration.css" rel="stylesheet">
    <link href="css/typing.css" rel="stylesheet"> 
    <link href="css/select2.min.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div class="card">
    <div class="card-header">
</div>
  <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        <form id="labform" enctype="multipart/form-data">
            <div class="table-responsive">
                <table class="table-sm">
                    <tbody>
                        <tr>
                            <td><label>Batch Ref:</label></td>
                            <td>
                           <select name="documentno" id="documentno" class="form-control-sm">
                          </select>
                                <input type="hidden" name="HeaderID" id="HeaderID">
                                <input type="hidden" name="batchno" id="batchnoID">
                            </td>
                        </tr>
                        <tr>
                            <td><label>DATE:</label></td>
                            <td>
                                <input tabindex="1" type="date" name="date" id="date" class="form-control-sm" size="11" maxlength="11" autofocus="autofocus" autocapitalize="none" autocorrect="off" spellcheck="false">
                            </td>
                        </tr>
                        
                        <tr>
                            <td><label>Client Account:</label></td>
                            <td>
                                <input tabindex="4" type="text" name="CustomerName" id="CustomerName" class="form-control-sm" value="" placeholder="Search a customer name" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false"  onkeyup="handleCustomerNameInput(event)">
                                <input type="hidden" name="CustomerID" id="CustomerID">
                                       <input type="hidden" name="tablecount" id="tablecount">
                     
                            </td>
                        </tr>
                        <tr>
                            <td><label>Sampled By:</label></td>
                            <td>
                                <input type="text" name="sampledby" id="sampledby" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                            </td>
                        </tr>
                        <tr>
                            <td><label>Sampling Method:</label></td>
                            <td>
                                <input type="text" name="SamplingMethod" id="SamplingMethod" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                            </td>
                        </tr>
                        <tr>
                            <td><label>Sampling Date:</label></td>
                            <td>
                                <input type="date" name="samplingdate" id="samplingdate" class="form-control-sm" size="11" maxlength="10" autocapitalize="none" autocorrect="off" spellcheck="false">
                            </td>
                        </tr>
                        <tr>
                            <td><label>Customer LPO No:</label></td>
                            <td>
                                <input type="text" name="Orderno" id="Orderno" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                            </td>
                        </tr>
                            <tr>
                                    
                                       <div id="sampleTableContainer" style="max-height:300px; overflow-y:auto;">
                                         <table class="table table-bordered table-hover table-sm align-middle" id="sampleTable">
                                          <thead class="table-light">
<tr>
                                                 <th style="min-width: 200px;">Standard Name</th>
                                                 <th style="min-width: 150px;">Matrix Package</th>
                                                 <th style="min-width: 100px;">Number of Samples</th>
                                                 <th style="min-width: 150px;">Standard Kit Units</th>
                                                 <th style="min-width: 150px;">Product Batch No</th>
                                                 <th style="min-width: 120px;">Batch Size (Optional)</th>
                                                 <th style="min-width: 160px;">Date of Manufacture</th>
                                                 <th style="min-width: 160px;">Date of Expiry</th>
                                                 <th style="min-width: 160px;">Chilled Date of Expiry</th>
                                                 <th style="min-width: 160px;">Frozen Date of Expiry</th>
                                                 <th style="min-width: 250px;">Picture of Sample</th>
                                                 <th style="min-width: 150px;">Sample Source</th>
                                                 <th style="min-width: 150px;">Sample Name</th>
                                                 <th style="min-width: 150px;">Sample Method</th>
                                                 <th style="min-width: 150px;">Condition of Sample</th>
                                                 <th style="min-width: 80px;">Action</th>
                                             </tr>
                                          </thead>
                                          <tbody id="sampleRows"></tbody>
                                        </table>
                                         <br>
                                        <hr>  
                                       </div>
                                        <p><div id="registrationsumary"></div></p>
                                    </td>
                                </tr>
                       <tr>
                            <td colspan="2">
                                <div id="samplehtml"></div>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
            </div>
            
            <div>
                <button type="submit" id="mypostid" name="post" class="btn btn-primary"><i class="fas fa-save"></i>🚀 SAVE</button>
            </div>
        </form>
    </div>
</div>


<script src="js/select2.min.js" type="text/javascript"></script>
<script src="js/upatesampleregistration.js" type="text/javascript"></script>
     
</body>
</html>