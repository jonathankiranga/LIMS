// Add remove loading class on body element based on Ajax request status
$(document).on({
    ajaxStart: function(){
        $("body").addClass("loading"); 
    },
    ajaxStop: function(){ 
        $("body").removeClass("loading"); 
    }    
});

$(document).ready(function() {
   
   $("input[type='submit']").addClass('btn-sm');
   $("input[type='button']").addClass('btn-sm');
    
    	function filterGlobal()
	{
		$('.register').DataTable().search(
			$('#global_filter').val(),
			$('#global_regex').prop('checked'),
			$('#global_smart').prop('checked')
		).draw();
	}

	function filterColumn(i)
	{
		$('.register').DataTable().column(i).search(
			$('#col' + i + '_filter').val(),
			$('#col' + i + '_regex').prop('checked'),
			$('#col' + i + '_smart').prop('checked')
		).draw();
	}

	$('input.global_filter').on('keyup click', function ()
	{
		filterGlobal();
	});

	$('input.column_filter').on('keyup click', function ()
	{
		filterColumn($(this).parents('tr').attr('data-column'));
	});

    
    
    $("#searchProstock").click(function(){
        $.post("includes/ProductionAjax.php",{
          offset: $("#searchProstock").offset(),
          top: $(window).scrollTop(),
          Stockfind: $("#Prostockname").val()
         },function(data){
           $("#findStock").remove();
           $("body").append(data);
         });
    });
    
    
     $("#searchrawstock").click(function(){
        $.post("includes/ProductionAjax.php",{
          offset: $("#searchrawstock").offset(),
          top: $(window).scrollTop(),
          stockname: $("#stockname").val()
         },function(data){
           $("#findStock").remove();
           $("body").append(data);
              
        });
    });
     
    
   $("#TaskActivityDate").change(function(){
        $.post("includes/ProductionAjax.php",{
           TASKFORM: $("#TaskActivityDate").val(),
           TaskName: $("#TaskName").val()
         },function(data){
          $("#TaskReport tbody").remove();
          $("#TaskReport").append('<tbody></tbody>');
          $("#TaskReport tbody").append(data);  
          
        });
    }); 
    
    
  $("a, button, input,select").click(function(){
        sessionStorage.scrolly = $(window).scrollTop();
  });
  
  if (sessionStorage.scrolly) {
     $(window).scrollTop(sessionStorage.scrolly);
    sessionStorage.clear();
  }
  
 
} );
