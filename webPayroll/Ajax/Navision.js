$(document).ready(function() {         
    
    $(".navconfig").focus(function(){
        $.post("Ajax/GetNavdetails.php",{
           VendorPage: $('#navsearchvendor').val(),
           NavPage: $('#navpage').val()
        },function(data){
           $(".navconfig").empty().append(data)
        });
     });   
           
    
    $("#DynamicsNav").ready(function(){
        $.post("Ajax/GetNavdetails.php",{
           DisplayCompany:'DisplayCompany',
           DefaulCompany: $('#WEBnavcompany').val()
        },function(data){
           $("#DynamicsNav").empty().append(data)
        });
       });
      
      
    $("#PostToDynamics").click(function(){
           $.post("Ajax/GetNavdetails.php",{
               PostPayrollId:$('#payrollid').val()
           },function(data){
              alert(data)
            });
    });
   
   
    $("#navdim").focusin(function(){
             $.post("Ajax/GetNavdetails.php",{
                Dimensions: 'YES'
             },function(data){
                $("#navdim").empty().append(data)
             });
    });
    
 } );