$(document).on({
	ajaxStart: function ()
	{
		$("body").addClass("loading");
	},
	ajaxStop: function ()
	{
		$("body").removeClass("loading");
	}
});


$(document).ready(function ()
{
	$("input[type='submit']").addClass('btn-sm');
	$("input[type='button']").addClass('btn-sm');

	$('.register').DataTable({
		"order": []
	});
	$('.statement').DataTable({
		"order": []
	});
	$('#cashbookdebit').DataTable({
		"order": []
	});
	$('#cashbookcredit').DataTable({
		"order": []
	});

	$('#flexCheckChecked3').change(function (){
		if ($('#flexCheckChecked2').prop('checked'))
		{
			$('#flexCheckChecked2').prop('checked', false);
		}

		if ($('#flexCheckChecked7').prop('checked'))
		{
			$('#flexCheckChecked7').prop('checked', false);
		}

	});


	$('#flexCheckChecked2').change(function (){
		if ($('#flexCheckChecked3').prop('checked'))
		{
			$('#flexCheckChecked3').prop('checked', false);
		}
	});


	$('#flexCheckChecked6').change(function ()
	{
		if ($(this).prop('checked'))
		{
			$('#flexCheckChecked2').prop('checked', true);
		}

		if ($('#flexCheckChecked1').prop('checked'))
		{
			$('#flexCheckChecked1').prop('checked', false);
		}

		if ($('#flexCheckChecked3').prop('checked'))
		{
			$('#flexCheckChecked3').prop('checked', false);
		}

		if ($('#flexCheckChecked7').prop('checked'))
		{
			$('#flexCheckChecked7').prop('checked', false);
		}
	});


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

	$("#selectjournaltoedit").click(function ()
	{
		$.post("includes/JournalAjax.php",
		{
			journaldate: $("#date").val(),
			journalfind: $("#DocumentNO").val()
		}, function (data)
		{       $("#journalspan").empty();
			$("#journalspan").append(data);
		});
	});
        

        
	$("#GetBankData").click(function ()
	{
		$.post("includes/bankreconciliationAJAX.php",
		{
			Bank_Code: $("#bankselected").val(),
			statementstartdate: $("#statementstartdate").val(),
			enddate: $("#bankrecondate").val()
		}, function (data)
		{
			$("#filtereddata").remove();
			$("#loadbankdata").append(data);
		});
	});


	$("#searchchart").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchchart").offset(),
			height: $(window).scrollTop(),
			Chartfind: 'yes'
		}, function (data)
		{
			$("#findSchart").remove();
			$("body").append(data);
		});
	});


	$("#searchchart2").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchchart2").offset(),
			height: $(window).scrollTop(),
			Chartfind: 'reload'
		}, function (data)
		{
			$("#findSchart").remove();
			$("body").append(data);
		});
	});


	$("#filtercustomer").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#filtercustomer").offset(),
			height: $(window).scrollTop(),
			filtercustomer: $("#CustomerName").val()
		}, function (data)
		{
			$("#findcustomer").remove();
			$("body").append(data);
		});
	});



	$("#searchcustomer").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchcustomer").offset(),
			height: $(window).scrollTop(),
			Customerfind: $("#CustomerName").val()
		}, function (data)
		{
			$("#findcustomer").remove();
			$("body").append(data);
		});
	});


	$("#vatrefresh").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			vatrefresh: $("#CustomerID").val()
		}, function (data)
		{
			$("#salesoderslist").remove();
			$("#SalesResults").append(data);
		});
	});




	$("#searchvendor").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchvendor").offset(),
			height: $(window).scrollTop(),
			Vendorfind: $("#VendorName").val()
		}, function (data)
		{
			$("#findVendor").remove();
			$("body").append(data);
		});
	});


	$("#searchstock").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchstock").offset(),
			height: $(window).scrollTop(),
			Stockfind: $("#stockname").val()
		}, function (data)
		{
			$("#findStock").remove();
			$("body").append(data);
		});
	});

	$("#searchfixedassets").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchfixedassets").offset(),
			height: $(window).scrollTop(),
			Assetfind: $("#stockname").val()
		}, function (data)
		{
			$("#findStock").remove();
			$("body").append(data);
		});
	});


	$("#stocktransferitemcode").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			stocktransferitemcode: $("#stocktransferitemcode").val()
		}, function (data)
		{
			$("#UOMstocktransfer option").remove();
			$("#UOMstocktransfer").append(data);
		});
	});


	$("#searchemployee").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			offset: $("#searchemployee").offset(),
			height: $(window).scrollTop(),
			EmployeeNamefind: $("#EmployeeName").val()
		}, function (data)
		{
			$("#EmployeeNamefind").remove();
			$("body").append(data);
		});
	});


	$("#Checkwhenpaid").click(function ()
	{
		$.post("includes/ConnectAjax.php",
		{
			Checkwhenpaid: 'yes'
		}, function (data)
		{
			alert(data);
		});
	});


	var lastSel = $("#transactiontype option:selected");
	$("#transactiontype").change(function ()
	{
		lastSel.prop("selected", true);
	});

	$("#transactiontype").click(function ()
	{
		lastSel = $("#transactiontype option:selected");
	});


	$("a, button, input,select").click(function ()
	{
		sessionStorage.scrolly = $(window).scrollTop();
	});

	if (sessionStorage.scrolly)
	{
		$(window).scrollTop(sessionStorage.scrolly);
		sessionStorage.clear();
	}


});