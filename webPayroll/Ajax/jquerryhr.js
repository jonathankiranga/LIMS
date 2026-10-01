$(document).ready(function() {
    $("input[type='submit']").addClass('btn btn-default');
    $("input[type='button']").addClass('btn btn-info');
   
   
   
    $('#getleaveid').click(function(){
       $.post("Ajax/GetNextHrCode.php",{
          leavid: 'Yes'
        },function(data){
            $("#prleaveid").val(data);
      });
    });  
    
    $('#GetPrNo').click(function(){
       $.post("Ajax/GetNextHrCode.php",{
          PrType: $("#PrType").val()
        },function(data){
            $("#pfidno").val(data);
      });
    });  
   
    $('#validatenewbank').focusout(function(){
       $.post("Ajax/validatebankbranch.php",{
          code:$("#validatenewbank").val()
        },function(data){
          if(data>0){
                 $("#validatenewbank").val(""),
                 alert('This code is used by another bank')
            }
      });
    });  
         
    $('#validatenewbankbranch').focusout(function(){
       $.post("Ajax/validatebankbranch.php",{
          parentcode:$("#bankfilter").val(),
          branchcode:$("#validatenewbankbranch").val()
        },function(data){
            if(data>0){
                 $("#validatenewbankbranch").val(""),
                 alert('This code is used by another bank')
            }
      });
    });    
            
    $('#bankfilter').click(function(){
       $.post("Ajax/getbranch.php",{
          parentcode:$("#bankfilter").val(),
          code:$("#branchfilter").val()
        },function(data){
          $("#branchfilter").empty().append(data)
      });
    });
           
    $('#jobgroup').click(function(){
        $.post("Ajax/getsalaryscale.php",{
           jobgroup:$("#jobgroup").val()
        },function(data){
           $("#salaryscale").empty().append(data)
        });
     });
                           
   
  $("a, button, input,select").click(function(){
        sessionStorage.scrolly=$(window).scrollTop();
    });

   
    if (sessionStorage.scrolly) {
        $(window).scrollTop(sessionStorage.scrolly);
        sessionStorage.clear();
     }
});
    