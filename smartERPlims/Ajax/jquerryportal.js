$(document).ready(function() {
    $("a, button, input,select").click(function(){
        sessionStorage.scrolly=$(window).scrollTop();
    });
 
    if (sessionStorage.scrolly) {
        $(window).scrollTop(sessionStorage.scrolly);
        sessionStorage.clear();
   }
  
  $('#SendPword').click(function(){
        $.post("Ajax/SendEmail.php",{
           getemail:$("#getEmail").val()
        },function(data){
           $("#Hassentemail").append(data)
        });
     });
 
 
 
 
});
    