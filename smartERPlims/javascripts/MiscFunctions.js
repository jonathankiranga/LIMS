
bkLib.onDomLoaded(function() {
   if(document.getElementById('ParameterName')){
       new nicEditor({buttonList : ['subscript','superscript']}).panelInstance('ParameterName');  
   }
   
  if(document.getElementById('Standard')){
    new nicEditor({buttonList :['subscript','superscript']}).panelInstance('Standard'); 
  }
  
  if(document.getElementById('interpretation')){
    new nicEditor({buttonList :['subscript','superscript']}).panelInstance('interpretation'); 
  }
  
}); 

function hidewindow(findStock){
    document.getElementById(findStock).setAttribute("style","visibility:hidden;display:none");
}


function limspricelistgrid(id,bc,name,qty,sp,rowid){
    document.getElementById('childid').value = rowid ;
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
    
} 


function loadBanks() {
    var currencyEl = document.getElementById('currencycode');
    var currency = currencyEl ? currencyEl.value : '';
    if(!currency) return;
    
    return fetch('ajax/GetBanks.php?currencycode=' + encodeURIComponent(currency))
    .then(function(response){ return response.json(); })
    .then(function(banks){
        var bank1 = document.getElementById('Bank_Code');
        var bank2 = document.getElementById('Bank_Code2');
        bank1.innerHTML = '<option></option>';
        bank2.innerHTML = '<option></option>';
        
        var savedBank1El = document.getElementById('savedBank1');
        var savedBank2El = document.getElementById('savedBank2');
        var savedBank1 = savedBank1El ? savedBank1El.value : '';
        var savedBank2 = savedBank2El ? savedBank2El.value : '';
        
        banks.forEach(function(bank){
            var opt1 = document.createElement('option');
            opt1.value = bank.accountcode;
            opt1.text = (bank.bankName || '').trim() + ' ' + (bank.BranchName || '') + ' ' + bank.currency;
            opt1.selected = (bank.accountcode === savedBank1);
            bank1.appendChild(opt1);
            
            var opt2 = document.createElement('option');
            opt2.value = bank.accountcode;
            opt2.text = (bank.bankName || '').trim() + ' ' + (bank.BranchName || '') + ' ' + bank.currency;
            opt2.selected = (bank.accountcode === savedBank2);
            bank2.appendChild(opt2);
        });

        // Bank 1 and Bank 2 must never hold the same account.
        var syncBankOptions = function () {
            var self = this;
            if (bank1.value !== '' && bank1.value === bank2.value) {
                if (self === bank2) { bank1.value = ''; } else { bank2.value = ''; }
            }
            var v1 = bank1.value;
            var v2 = bank2.value;
            var i;
            for (i = 0; i < bank1.options.length; i++) {
                bank1.options[i].disabled = (bank1.options[i].value !== '' && bank1.options[i].value === v2);
            }
            for (i = 0; i < bank2.options.length; i++) {
                bank2.options[i].disabled = (bank2.options[i].value !== '' && bank2.options[i].value === v1);
            }
        };
        if (!bank1.getAttribute('data-banksynced')) {
            bank1.addEventListener('change', syncBankOptions);
            bank2.addEventListener('change', syncBankOptions);
            bank1.setAttribute('data-banksynced', '1');
            bank2.setAttribute('data-banksynced', '1');
        }
        if (bank1.value !== '' && bank1.value === bank2.value) {
            bank2.value = '';
        }
        syncBankOptions();
    });
}


function Unsavedpricelistgrid(id,bc,name,qty,sp,uom,rowid){
    document.getElementById('childid').value = rowid ;
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
    options = document.getElementById('packid');
    
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(uom)> -1){
                      selectedOption.selected="selected";
                 }
       }
} 

function multiply(AId,BId,Cid){
   A= document.getElementById(AId);
   B= document.getElementById(BId);
   //parseFloat('$148,326.00'.replace(/\$|,/g, ''))
   One = A.value;
   var stringWithCommas = B.value;
   two = stringWithCommas.replace(/,/g, '');
   document.getElementById(BId).value = two;
    Ans = One * two  ;
    document.getElementById(Cid).value = Ans;
    
} 

function divideCheckList(AId,BId,Cid){
   A= document.getElementById(AId);
   B= document.getElementById(Cid);
   
   One = A.value;
   var stringWithCommas = B.value;
   two = stringWithCommas.replace(/,/g, '');
   document.getElementById(BId).value = two;
   Ans = two/One ;
   
   document.getElementById(BId).value = Ans;
}

function divideCheck(AId,BId,Cid){
   A= document.getElementById(AId);
   B=document.getElementById(Cid);
   
   One = A.value;
   two = B.value;
   Ans =  two/One ;
   if(document.getElementById(BId).value=='' || document.getElementById(BId).value==0){
     document.getElementById(BId).value = Ans;
  }
}

function prodInventory(customerid,customername){
    document.getElementById('proitemcode').value=customerid;
    document.getElementById('Prostockname').value=customername;
    document.getElementById('findStock').setAttribute("style","visibility:hidden;display:none");
    
} 

function ItemInventory(customerid,customername){
    document.getElementById('stockitemcode').value=customerid;
    document.getElementById('stockname').value=customername;
    document.getElementById('findStock').setAttribute("style","visibility:hidden;display:none");
    
} 

function CopyFieldValue(values,id){
    checkvalue = document.getElementById(id) ;
    if(checkvalue.value==''){
        checkvalue.value=values;
    }
}

function showSmartAlert(message, title, type){
    SmartDialog.alert(message, title || 'Alert', type || 'info');
}

function validateform(id){
    checkvalue = document.getElementById(id) ;
        if(checkvalue.value==''){
           showSmartAlert("Something is missing ", "Warning", "warning"); return false; 
       }else
           return true;
}

function ForcePDFPrint(id){
     document.getElementById(id).click() ;
}


function SalesInventory(id,bc,name){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('myStockInput').value = "";
    document.getElementById('line_no').value ="" ;
 
    fB = document.getElementById('salesform');
    fB.click();
 } 

function posInventoryvat(id,vatc,name,container){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('stockname').value = name ;
    document.getElementById('myStockInput').value = "";
         
    // this code is awsome it sslects a combo box item 
    options = document.getElementById('vatcategory');
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(vatc)> -1){
                      selectedOption.selected="selected";
                 }
       }
       
    // this code is awsome it sslects a combo box item 
       receivedid = document.getElementById('receivedid');
         for(i = 0; i < receivedid.length; i++){
             selectedOption = receivedid[i];
                  if(selectedOption.innerHTML.indexOf(container)> -1){
                      selectedOption.selected="selected";
                 }
       }
       
    fB = document.getElementById('salesform');
    fB.click();
 } 
 
function posInventory(id,bc,name,price){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('myStockInput').value = "";
    if(document.getElementById('unitprice')){
        document.getElementById('unitprice').value = price || '';
    }
    if(document.getElementById('sp')){
        document.getElementById('sp').value = price || '';
    }
    if(document.getElementById('discountpercent')){
        document.getElementById('discountpercent').value = 0;
    }
   
    fB = document.getElementById('salesform');
    fB.click();
 } 
 
function posInventoryUnits(id,bc,name,units,isbitumen){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('myStockInput').value = "";
    
        if(document.getElementById('VCF').value==""){
           document.getElementById('VCF').value=1;
        }
       
        if(isbitumen==1){
            document.getElementById('VCF').readOnly = false;
        }

        if(isbitumen==0){
            document.getElementById('VCF').readOnly = true;
        }
  
     // this code is awsome it sslects a combo box item 
    options = document.getElementById('packid');
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(units)> -1){
                      selectedOption.selected="selected";
                 }
       }
       
 } 
 
 
 
// Handle certificate selection and load sample IDs
    function fetchlabref(documentNo){
        $.ajax({
            url: 'Ajax/getsampleidforbatchAjax.php?documentno=' + encodeURIComponent(documentNo),
            type: 'GET',
            dataType: 'json',
            success: function(response){
                if(response.success){
                    if(response.sampleIds && response.sampleIds.length > 0){
                        var sampleSelect = $('#sampleid');
                        sampleSelect.empty();
                        $.each(response.sampleIds, function(index, sample){
                            sampleSelect.append('<option value="' + sample.sampleid + '">' + sample.sampleid + '</option>');
                        });
                    }
                   
                }
            },
            error: function(){
                alert('Error loading samples for document: ' + documentNo);
            }
        });
    } 
 
function LabOrderInventorygrid(line_no,id,bc,name,qty,sp,pak,uom,disc){
    document.getElementById('line_no').value = line_no ;
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
    document.getElementById('packzize').value = pak;
    if(document.getElementById('discountpercent')){
        document.getElementById('discountpercent').value = disc || 0;
    }
        
    const docno = document.getElementById("coa_documentno");
        if (docno) {
            fetchlabref(docno.value);   // ← Now calls your function
        }
    
    
    options = document.getElementById('sampleid');
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(uom)> -1){
                      selectedOption.selected="selected";
                 }
       }
    
} 


function posInventorygrid(id,bc,name,qty,sp){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
} 

function SalesOrderInventorygrid(line_no,id,bc,name,qty,sp,pak,uom,disc){
    document.getElementById('line_no').value = line_no ;
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
    document.getElementById('packzize').value = pak;
    if(document.getElementById('discountpercent')){
        document.getElementById('discountpercent').value = disc || 0;
    }
    options = document.getElementById('packid');
    
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(uom)> -1){
                      selectedOption.selected="selected";
                 }
       }
    
} 

function purchaseOrderInventorygrid(id,bc,name,qty,recqty,sp,pak,discount){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('recqty').value = recqty;
    document.getElementById('cost').value = sp;
    document.getElementById('packzize').value = pak; 
    document.getElementById('discount').value = discount;
} 

function ProductionGrid(id,bc,name,qty){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
} 

function ProductionGridtwo(id,bc,name,qty,bitname){
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    options = document.getElementById('bitcode') ;
    
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(bitname)> -1){
                      selectedOption.selected="selected";
                 }
       }
     
} 

function pricelistgrid(id,bc,name,qty,sp,uom,rowid,contname){
   
    document.getElementById('rowid').value = rowid ;
    document.getElementById('stockitemcode').value = id ;
    document.getElementById('barcode').value = bc ;
    document.getElementById('stockname').value = name ;
    document.getElementById('qty').value = qty;
    document.getElementById('sp').value = sp;
    options = document.getElementById('packid');
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(uom)> -1){
                      selectedOption.selected="selected";
                 }
       }
   options = document.getElementById('containerid');
        // this code is awsome it sslects a combo box item 
         for(i = 0; i < options.length; i++){
             selectedOption = options[i];
                  if(selectedOption.innerHTML.indexOf(contname)> -1){
                      selectedOption.selected="selected";
                 }
       }  
    
} 

function mysetAccountFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myAccountInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myAccountTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[1];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function myAccountFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myAccountInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myAccountTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function selectaccount(code,aname){
    document.getElementById('accountcode').value=code;
    document.getElementById('accountname').value=aname;
    document.getElementById('findSchart').setAttribute("style","visibility:hidden;display:none");
} 

 
function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'excel_data.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}

        
var tableToExcel = (function() {
  var uri = 'data:application/vnd.ms-excel;base64,'
    , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>'
    , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
    , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
  return function(table, name) {
    if (!table.nodeType) table = document.getElementById(table)
    var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
    window.location.href = uri + base64(format(template, ctx))
  }
})()

function mycustomersFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("mycustomersInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("mycustomersTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function myVendorFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myVendorInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myVendorTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function myStockFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myStockInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myStockTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[1];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
} 


function multStockFunction() {
    
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("multStockInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("multStockTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
} 





function myRatesFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myStockInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myStockTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[1];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
} 

function myAssetFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myAssetInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myAssetTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function myEmployeeFunction() {
  // Declare variables 
  var input, filter, table, tr, td, i;
  input = document.getElementById("myEmployeeInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("myEmployeeTable");
  tr = table.getElementsByTagName("tr");

  // Loop through all table rows, and hide those who don't match the search query
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    } 
  }
}

function selectemployee(customerid,customername){
    document.getElementById('EmployeeID').value=customerid;
    document.getElementById('EmployeeName').value=customername;
    document.getElementById('EmployeeNamefind').setAttribute("style","visibility:hidden;display:none");
    fB=document.getElementById("salesform");
    fB[0].click();
} 

function selectservice(customerid,customername){
    document.getElementById('stockitemcode').value=customerid;
    document.getElementById('stockname').value=customername;
    document.getElementById('findJob').setAttribute("style","visibility:hidden;display:none");
    fB=document.getElementById('salesform');
    fB[0].click();
} 

function selectInventory(customerid,customername){
    document.getElementById('stockitemcode').value=customerid;
    document.getElementById('stockname').value=customername;
    document.getElementById('findStock').setAttribute("style","visibility:hidden;display:none");
    fB=document.getElementById('salesform');
    fB[0].click();
} 

function filtercustomer(customerid,customername){
    document.getElementById('CustomerID').value=customerid;
    document.getElementById('CustomerName').value=customername;
    document.getElementById('findcustomer').setAttribute("style","visibility:hidden;display:none");
    document.getElementById('f1lt3r').click();
} 

function selectcustomer(customerid,customername,curr_cod,salespersoncode){
    document.getElementById('CustomerID').value=customerid;
    document.getElementById('CustomerName').value=customername;
    document.getElementById('currencycode').value=curr_cod;
    
     options = document.getElementById('salespersoncode');
   // this code is awsome it sslects a combo box item 
    for(i = 0; i < options.length; i++){
        selectedOption = options[i];
             if(selectedOption.innerHTML.indexOf(salespersoncode)> -1){
                 selectedOption.selected="selected";
            }
        
   }
    
    document.getElementById('findcustomer').setAttribute("style","visibility:hidden;display:none");
} 

function selectvendor(customerid,customername,curr_cod){
    document.getElementById('VendorID').value=customerid;
    document.getElementById('VendorName').value=customername;
    document.getElementById('currencycode').value=curr_cod;
    document.getElementById('findVendor').setAttribute("style","visibility:hidden;display:none");
} 


function selectFixedassets(customerid,customername){
    document.getElementById('stockitemcode').value=customerid;
    document.getElementById('stockname').value=customername;
    document.getElementById('AssetStock').setAttribute("style","visibility:hidden;display:none");
    fB=document.getElementById('salesform');
    fB[0].click();
} 

function GetAllCleared(bankendbalance){
 var TotalCleared=0,Difference=0,Balbfwd=0,CRamount=0;
 var DBamount=0;
   
    DBamount = document.getElementById('clearedDB').value;
     Balbfwd = document.getElementById('lastreconbalance').value;
     CRamount = document.getElementById('clearedCR').value;
     
   TotalCleared = (((+Balbfwd) + (+DBamount))- (+CRamount));
   Difference= (+bankendbalance)- (+TotalCleared);
   
   document.getElementById('cleared').value = Math.round(TotalCleared,1);
   document.getElementById('Uncleared').value = Math.round(Difference,1);
    
}

function GetDBCleared(nAmount,Check){
 var TotalCleared=0,Difference=0,
         NewTotal=0,Balbfwd=0,
         CRamount=0,bankendbalance=0,
         DBamount=0;
   
    DBamount = document.getElementById('clearedDB').value;
    if(Check.checked==true){
        NewTotal = (+nAmount) + (+DBamount) ;
        document.getElementById('clearedDB').value =  NewTotal  ;
    }else{
       NewTotal = (+DBamount) - (+nAmount) ;
       document.getElementById('clearedDB').value =  NewTotal  ;
    }
     
     Balbfwd   = document.getElementById('lastreconbalance').value;
     CRamount  = document.getElementById('clearedCR').value;
     bankendbalance = document.getElementById('bankendbalance').value;
     
   TotalCleared = (((+Balbfwd) + (+NewTotal))- (+CRamount));
   Difference= (+bankendbalance)- (+TotalCleared);
   
   document.getElementById('cleared').value = Math.round(TotalCleared,1);
   document.getElementById('Uncleared').value = Math.round(Difference,1);
    
}

function GetCRCleared(Amount,Check){
var TotalCleared=0,Difference=0,NewTotal=0,Balbfwd=0,CRamount=0,bankendbalance=0,DBamount=0;
     
    CRamount = document.getElementById('clearedCR').value;
       
    if(Check.checked==true){
        NewTotal=(+CRamount) - (+Amount);
        document.getElementById('clearedCR').value= NewTotal;
    }else{
        NewTotal= (+CRamount) + (+Amount);
         document.getElementById('clearedCR').value=NewTotal;
    }
    
   Balbfwd = document.getElementById('lastreconbalance').value;
   DBamount =  document.getElementById('clearedDB').value;
   bankendbalance = document.getElementById('bankendbalance').value;
   
   TotalCleared = (((+Balbfwd) + (+DBamount))- (+NewTotal));
   Difference= (+bankendbalance)- (+TotalCleared);
 
    document.getElementById('cleared').value =Math.round(TotalCleared,1);
    document.getElementById('Uncleared').value = Math.round(Difference,1);
}
 



  
function dragElement(elmnt) {
  var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
  if (document.getElementById(elmnt.id + "header")) {
    /* if present, the header is where you move the DIV from:*/
    document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
  } else {
    /* otherwise, move the DIV from anywhere inside the DIV:*/
    elmnt.onmousedown = dragMouseDown;
  }

  function dragMouseDown(e) {
    const evt = e;
    if (!evt) return;
    // get the mouse cursor position at startup:
    pos3 = evt.clientX;
    pos4 = evt.clientY;
    document.onmouseup = closeDragElement;
    // call a function whenever the cursor moves:
    document.onmousemove = elementDrag;
  }

  function elementDrag(e) {
    const evt = e;
    if (!evt) return;
    // calculate the new cursor position:
    pos1 = pos3 - evt.clientX;
    pos2 = pos4 - evt.clientY;
    pos3 = evt.clientX;
    pos4 = evt.clientY;
    // set the element's new position:
    elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
    elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
    
  }

  function closeDragElement() {
    /* stop moving when mouse button is released:*/
    document.onmouseup = null;
    document.onmousemove = null;
  }
}



function checkinput(input){
    if(input.value=='')
        return false;
}

function defaultControl(c){
    c.select();
    c.focus();
}

function ReloadForm(fB){
    fB.click();
}


function CalculateForm(fB){
    fB[0].click();
}

function rTN(e) {
    if (!e) return true;
    const k = e.key;
    // Allow control keys
    if (['Backspace','Tab','Enter','Escape','ArrowLeft','ArrowRight','Delete'].includes(k)) return true;
    // Allow numbers, dot, comma, minus, space
    return "0123456789.,- ".indexOf(k) > -1;
}

function rTI(e) {
    if (!e) return true;
    const k = e.key;
    // Allow control keys
    if (['Backspace','Tab','Enter','Escape','ArrowLeft','ArrowRight','Delete'].includes(k)) return true;
    // Allow digits only
    return "0123456789".indexOf(k) > -1;
}

function rLocaleNumber(){
	var Lang = document.getElementById('Lang').value;
	switch(Lang){
		case 'US':
			var patt = /(?:^(-)?([1-9]{1}\d{0,2}(?:,?\d{3})*(?:\.\d{1,})?)$)|(?:^(-)?(0?\.\d{1,})$)|(?:^0$)/;
			break;
		case 'IN':
			var patt = /(?:^(-)?([1-9]{1}\d{0,1},)?(\d{2},)*(\d{3})(\.\d+)?$)|(?:^(-)?[1-9]{1}\d{0,2}(\.\d+)?$)|(?:^(-)?(0?\.\d{1,})$)|(?:^0$)/;
			break;
		case 'EE':
			var patt = /(?:^(-)?[1-9]{1}\d{0,2}(?:\s?\d{3})*(?:\.\d{1,})?$)|(?:^(-)?(0?\.\d{1,})$)|(?:^0$)/;
			break;
		case 'FR':
			var patt = /(?:^(-)?[1-9]{1}\d{0,2}(?:\s?\d{3})*(?:,\d{1,})?$)|(?:^(-)?(0?,\d{1,})$)|(?:^0$)/;
			break;
		case 'GM':
			var patt = /(?:^(-)?[1-9]{1}\d{0,2}(?:\.?\d{3})*(?:,\d{1,})?$)|(?:^(-)?(0?,\d{1,})$)|(?:^0$)/;
			break;
		default:
			showSmartAlert('something is wrong with your language setting', 'Error', 'error');


	}
	if(patt.test(this.value)){
		this.setCustomValidity('');
		return true;

	}else{
		this.setCustomValidity('The number format is wrong');
		return false;
	};
}
function assignComboToInput(c,i){
	i.value=c.value;
}
function inArray(v,tA,m){
	for (i=0;i<tA.length;i++) {
		if (v==tA[i].value) {
			return true;
		}
	}
	showSmartAlert(m, 'Warning', 'warning');
	return false;
}
function isDate(dS,dF){
	var mA=dS.match(/^(\d{1,2})(\/|-|.)(\d{1,2})(\/|-|.)(\d{4})$/);
	if (mA==null){
		showSmartAlert("Please enter the date in the format "+dF, 'Warning', 'warning');
		return false;
	}
	if (dF=="d/m/Y"){
		d=mA[1];
		m=mA[3];
	}else{
		d=mA[3];
		m=mA[1];
	}
	y=mA[5];
	if (m<1 || m>12){
		showSmartAlert("Month must be between 1 and 12", 'Warning', 'warning');
		return false;
	}
	if (d<1 || d>31){
		showSmartAlert("Day must be between 1 and 31", 'Warning', 'warning');
		return false;
	}
	if ((m==4 || m==6 || m==9 || m==11) && d==31){
		showSmartAlert("Month "+m+" doesn`t have 31 days", 'Warning', 'warning');
		return false;
	}
	if (m==2){
		var isleap=(y%4==0);
		if (d>29 || (d==29 && !isleap)){
			showSmartAlert("February "+y+" doesn`t have "+d+" days", 'Warning', 'warning');
			return false;
		}
	}
	return true;
}
function eitherOr(o,t){
	if (o.value!='') t.value='';
	else if (o.value=='NaN') o.value='';
}
/*Renier & Louis (info@tillcor.com) 25.02.2007
Copyright 2004-2007 Tillcor International
*/
days=new Array('Su','Mo','Tu','We','Th','Fr','Sa');
months=new Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
dateDivID="calendar";
function Calendar(md,dF){
	iF=document.getElementsByName(md).item(0);
	pB=iF;
	x=pB.offsetLeft;
	y=pB.offsetTop+pB.offsetHeight;
	var p=pB;
	while (p.offsetParent){
		p=p.offsetParent;
		x+=p.offsetLeft;
		y+=p.offsetTop;
	}
	dt=convertDate(iF.value,dF);
	nN=document.createElement("div");
	nN.setAttribute("id",dateDivID);
	nN.setAttribute("style","visibility:hidden;");
	document.body.appendChild(nN);
	cD=document.getElementById(dateDivID);
	cD.style.position="absolute";
	cD.style.left=x+"px";
	cD.style.top=y+"px";
	cD.style.visibility=(cD.style.visibility=="visible" ? "hidden" : "visible");
	cD.style.display=(cD.style.display=="block" ? "none" : "block");
	cD.style.zIndex=10000;
	drawCalendar(md,dt.getFullYear(),dt.getMonth(),dt.getDate(),dF);
}
function drawCalendar(md,y,m,d,dF){
	var tD=new Date();
	if ((m>=0) && (y>0)) tD=new Date(y,m,1);
	else{
		d=tD.getDate();
		tD.setDate(1);
	}
	TR="<tr>";
	xTR="</tr>";
	TD="<td class='dpTD' onMouseOut='this.className=\"dpTD\";' onMouseOver='this.className=\"dpTDHover\";'";
	xTD="</td>";
	html="<table class='dpTbl'>"+TR+"<th colspan=\"3\">"+months[tD.getMonth()]+" "+tD.getFullYear()+"</th>"+"<td colspan=\"2\">"+
	getButtonCode(md,tD,-1,"&lt;",dF)+xTD+"<td colspan=\"2\">"+getButtonCode(md,tD,1,"&gt;",dF)+xTD+xTR+TR;
	for(i=0;i<days.length;i++) html+="<th>"+days[i]+"</th>";
		html+=xTR+TR;
	for (i=0;i<tD.getDay();i++) html+=TD+"&nbsp;"+xTD;
	do{
		dN=tD.getDate();
		TD_onclick=" onclick=\"postDate('"+md+"','"+formatDate(tD,dF)+"');\">";
		if (dN==d) html+="<td"+TD_onclick+"<div class='dpDayHighlight'>"+dN+"</div>"+xTD;
		else html+=TD+TD_onclick+dN+xTD;
		if (tD.getDay()==6) html+=xTR+TR;
		tD.setDate(tD.getDate()+1);
	} while (tD.getDate()>1)
	if (tD.getDay()>0) for (i=6;i>tD.getDay();i--) html+=TD+"&nbsp;"+xTD;
		html+="</table>";
	document.getElementById(dateDivID).innerHTML=html;
}
function getButtonCode(mD,dV,a,lb,dF){
	nM=(dV.getMonth()+a)%12;
	nY=dV.getFullYear()+parseInt((dV.getMonth()+a)/12,10);
if (nM<0){
	nM+=12;
	nY+=-1;
}
return "<button onClick='drawCalendar(\""+mD+"\","+nY+","+nM+","+1+",\""+dF+"\");'>"+lb+"</button>";
}
function formatDate(dV,dF){
	ds=String(dV.getDate());
	ms=String(dV.getMonth()+1);
	d=("0"+dV.getDate()).substring(ds.length-1,ds.length+1);
	m=("0"+(dV.getMonth()+1)).substring(ms.length-1,ms.length+1);
	y=dV.getFullYear();
	switch (dF) {
		case "d/m/Y":
			return d+"/"+m+"/"+y;
		case "d.m.Y":
			return d+"."+m+"."+y;
		case "Y/m/d":
			return y+"/"+m+"/"+d;
		case "Y-m-d":
			return y+"-"+m+"-"+d;
		default :
			return m+"/"+d+"/"+y;
	}
}
function convertDate(dS,dF){
	var d,m,y;
	if (dF=="d.m.Y")
		dA=dS.split(".");
	else
		dA=dS.split("/");
	switch (dF){
		case "d/m/Y":
			d=parseInt(dA[0],10);
			m=parseInt(dA[1],10)-1;
			y=parseInt(dA[2],10);
			break;
	case "d.m.Y":
		d=parseInt(dA[0],10);
		m=parseInt(dA[1],10)-1;
		y=parseInt(dA[2],10);
		break;
	case "Y-m-d":
	case "Y/m/d":
		d=parseInt(dA[2],10);
		m=parseInt(dA[1],10)-1;
		y=parseInt(dA[0],10);
		break;
	default :
		d=parseInt(dA[1],10);
		m=parseInt(dA[0],10)-1;
		y=parseInt(dA[2],10);
		break;
}
return new Date(y,m,d);
}
function postDate(mydate,dS){
var iF=document.getElementsByName(mydate).item(0);
iF.value=dS;
var cD=document.getElementById(dateDivID);
cD.style.visibility="hidden";
cD.style.display="none";
iF.focus();
}
function clickDate(){
	Calendar(this.name,this.alt);
}
function changeDate(){
	isDate(this.value,this.alt);
}
function SortSelect() {
	selElem=this;
	var tmpArray = new Array();
	columnText=selElem.innerHTML;
	parentElem=selElem.parentNode;
	table=parentElem.parentNode;
	row = table.rows[0];
	for (var j = 0, col; col = row.cells[j]; j++) {
		if (row.cells[j].innerHTML==columnText) {
			columnNumber=j;
			if (selElem.className=="ascending") {
				selElem.className='descending';
				direction="a";
			} else {
				selElem.className='ascending';
				direction="d";
			}
		}
	}
	for (var i = 1, row; row = table.rows[i]; i++) {
		var rowArray = new Array();
		for (var j = 0, col; col = row.cells[j]; j++) {
			if (row.cells[j].tagName == 'TD' ) {
				rowArray[j]=row.cells[j].innerHTML;
				columnClass=row.cells[columnNumber].className;
			}
		}
		tmpArray[i]=rowArray;
	}
	tmpArray.sort(
		function(a,b) {
			if (direction=="a") {
				if (columnClass=="number") {
					return parseFloat(a[columnNumber])-parseFloat(b[columnNumber]);
				} else if (columnClass=="date") {
					da=new Date(a[columnNumber]);
					db=new Date(b[columnNumber]);
					return da>db;
				} else {
					return a[columnNumber].localeCompare(b[columnNumber])
				}
			} else {
				if (columnClass=="number") {
					return parseFloat(b[columnNumber])-parseFloat(a[columnNumber]);
				} else if (columnClass=="date") {
					da=new Date(a[columnNumber]);
					db=new Date(b[columnNumber]);
					return da<=db;
				} else {
					return b[columnNumber].localeCompare(a[columnNumber])
				}
			}
		}
	);
	for (var i = 0, row; row = table.rows[i+1]; i++) {
		var rowArray = new Array();
		rowArray=tmpArray[i];
		for (var j = 0, col; col = row.cells[j]; j++) {
			if (row.cells[j].tagName == 'TD' ) {
				row.cells[j].innerHTML=rowArray[j];
			}
		}
	}
	return;
}

function initial(){
	if (document.getElementsByTagName){
		var as=document.getElementsByTagName("a");
		for (i=0;i<as.length;i++){
			var a=as[i];
			if (a.getAttribute("href") &&
				a.getAttribute("rel")=="external")
				a.target="_blank";
		}
	}
	var ds=document.getElementsByTagName("input");
	for (i=0;i<ds.length;i++){
		if (ds[i].className=="date"){
			ds[i].onclick=clickDate;
			ds[i].onchange=changeDate;
		}
		if(ds[i].getAttribute("data-type") == 'no-illegal-chars') ds[i].pattern="(?!^ +$)[^?\'\u0022+.&\\\\><]*";
		if (ds[i].className=="number") ds[i].onkeypress=rTN;
		if (ds[i].className=="integer") ds[i].onkeypress=rTI;
		if (ds[i].className=="number"){

				ds[i].origonchange=ds[i].onchange;
				ds[i].newonchange=rLocaleNumber;
				ds[i].onchange=function(){
					if(this.origonchange)
						this.origonchange();
					this.newonchange();
				};

		}
	}
	var ds=document.getElementsByTagName("th");
	for (i=0;i<ds.length;i++){
		if (ds[i].className=="ascending") ds[i].onclick=SortSelect;
	}
}
window.onload=initial;
