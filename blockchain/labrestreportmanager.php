
    <style>
        .report-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .toolbar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .toolbar button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .toolbar button:hover {
            background: #2980b9;
        }
        .toolbar .fa {
            margin-right: 5px;
        }
        .search-container {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .no-results {
            text-align: center;
            color: red;
        }
        .report-header {
            width: 100%;
            margin-bottom: 20px;
        }
        .report-header td {
            padding: 10px;
            border: none;
            vertical-align: top;
        }
        .report-header tr:nth-child(odd) td {
            background-color: #f9f9f9;
        }
        .report-header div {
            margin-bottom: 5px;
        }
        #tempid, #humidityid, #notesid {
            font-family: monospace;
            font-size: 11px;
            font-style: italic;
        }
        #sampleImage {
            max-height: 100px;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }

            .report-container {
                background: white;
                padding: 20px;
                box-shadow: none;
                page-break-before: always;
            }

            .report-header {
                display: block;
                margin-bottom: 20px;
            }

            #resultsTable {
                width: 100%;
                border-collapse: collapse;
                page-break-inside: avoid;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #3498db;
                color: white;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            .no-results {
                text-align: center;
                color: red;
            }

            .report-container {
                page-break-after: always;
            }

            #resultsTable thead {
                display: table-header-group;
            }

            #resultsTable tbody tr {
                page-break-inside: avoid;
            }

            #printme {
                visibility: visible;
            }
        }
    </style>
    <link href="css/select2.min.css" rel="stylesheet" type="text/css"/>
    <div class="report-container">
        <div class="toolbar">
            <button id="printButton"><i class="fa fa-print"></i> Print</button>
        </div>
        <div class="search-container">
            <select id="searchSelect" style="width: 100%"></select>
        </div>
        <div id="printme">
            <table class="report-header" id="reportHeader">
                <tr>
                    <td><strong>Date:</strong></td>
                    <td><span id="reportDate"></span></td>
                    <td><strong>Batch No:</strong></td>
                    <td><span id="documentNo"></span></td>
                    <td colspan="4" rowspan="2">
                        <img id="sampleImage" src="" alt="Sample Image" class="img-fluid rounded border">
                    </td>
                </tr>
                <tr>
                    <td><strong>Customer ID:</strong></td>
                    <td><span id="customerID"></span></td>
                    <td><strong>Customer Name:</strong></td>
                    <td><span id="customerName"></span></td>
                </tr>
                <tr>
                    <td><strong>Sampling Date:</strong></td>
                    <td><span id="samplingDate"></span></td>
                    <td><strong>Sampled By:</strong></td>
                    <td><span id="sampledBy"></span></td>
                    <td><strong>Sampling Method:</strong></td>
                    <td><span id="samplingMethod"></span></td>
                </tr>
                <tr>
                    <td><strong></strong></td>
                    <td><span id="scopeOfWork"></span></td>
                    <td><strong>LPO no:</strong></td>
                    <td><span id="orderNo"></span></td>
                    <td><strong>External Sample Ref:</strong></td>
                    <td><span id="ExternalSample"></span></td>
                </tr>
                <tr>
                    <td><strong>SKU:</strong></td>
                    <td><span id="SKU"></span></td>
                    <td><strong>Batch No:</strong></td>
                    <td><span id="BatchNo"></span></td>
                    <td><strong>Batch Size:</strong></td>
                    <td><span id="BatchSize"></span></td>
                </tr>
                <tr>
                    <td><strong>Manf Date:</strong></td>
                    <td><span id="manDate"></span></td>
                    <td><strong>Exp Date:</strong></td>
                    <td><span id="ExpDate"></span></td>
                </tr>
            </table>
            <div id="resultsContainer">
                <table id="resultsTable">
                    <thead>
                        <tr>
                            <th>Sample ID</th>
                            <th>Test Name</th>
                            <th>Qualitative Result</th>
                            <th>Quantitative Status</th>
                            <th>Range Result</th>
                            <th>User Name</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div class="no-results" id="noResultsMessage" style="display: none;">No report available</div>
            </div>
        </div>
    </div>
    <script src="js/select2.min.js" type="text/javascript"></script>
    <script>
        $(document).ready(function() {
            const pageSize = 20;

            function loadSelectData(page) {
                $('#searchSelect').select2({
                    placeholder: 'Select Customer Name or Batch No',
                    allowClear: true,
                    ajax: {
                        url: 'ajax/fetch_report_init.php',
                        method: 'POST',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                searchTerm: params.term,
                                page: page,
                                limit: pageSize
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.items,
                                pagination: {
                                    more: data.hasMore
                                }
                            };
                        },
                        cache: true
                    }
                });
            }

            loadSelectData(1);

            $('#searchSelect').on('change', function() {
                $("#reportHeader").hide();
                const selectedValue = $(this).val();
                if (!selectedValue) {
                    $("#resultsTable tbody").empty();
                    $("#noResultsMessage").show();
                    $("#reportHeader").hide();
                    return;
                }
                $.ajax({
                    url: "ajax/fetch_report.php",
                    type: "POST",
                    data: { searchTerm: selectedValue },
                    dataType: "json",
                    success: function(data) {
                        $("#resultsTable tbody").empty();
                        if (data.length > 0) {
                            $("#reportDate").text(new Date(data[0].Date.split(' ')[0]).toISOString().split('T')[0]);
                            $("#documentNo").text(data[0].DocumentNo);
                            $("#customerName").text(data[0].CustomerName);
                            $("#customerID").text(data[0].CustomerID);
                            $("#sampledBy").text(data[0].SampledBy);
                            $("#samplingMethod").text(data[0].SamplingMethod);
                            $("#samplingDate").text(new Date(data[0].SamplingDate.split(' ')[0]).toISOString().split('T')[0]);
                            $("#orderNo").text(data[0].OrderNo);
                          //  $("#scopeOfWork").text(data[0].ScopeOfWork);
                            $("#SKU").text(data[0].SKU);
                            $("#BatchNo").text(data[0].BatchNo);
                            $("#BatchSize").text(data[0].BatchSize);
                            $("#manDate").text(new Date(data[0].ManufactureDate.split(' ')[0]).toISOString().split('T')[0]);
                            $("#ExpDate").text(new Date(data[0].ExpDate.split(' ')[0]).toISOString().split('T')[0]);
                            $("#ExternalSample").text(data[0].ExternalSample);
                            $("#sampleImage").attr("src",data[0].SampleFileKey);
                            $("#reportHeader").show();
                            data.forEach(function(row) {
                            $("#resultsTable tbody").append(`
                                <tr>
                                    <td>${row.SampleID}</td>
                                    <td>${row.ParameterName}</td>
                                    <td>${row.MRL_Result || 'N/A'}</td>
                                    <td>${row.ResultStatus || 'N/A'}</td>
                                    <td>${row.RangeResult || 'N/A'}</td>
                                    <td>${row.User_name}</td>
                                </tr>
                            `);
                        });
                        $("#noResultsMessage").hide();
                    } else {
                        $("#reportHeader").hide();
                        $("#noResultsMessage").show();
                    }
                },
                error: function() {
                     $("#reportHeader").hide();
                    $("#noResultsMessage").show();
                }
            });
        });
        
        $('#printButton').click(function() {
            var printContents = document.querySelector('#printme').innerHTML;
            var printStyles = `<style>
                @media print {
                    .report-container { background: white; padding: 20px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
                    .report-header { display: block; margin-bottom: 20px; }
                    #resultsTable { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #3498db; color: white; }
                    tr:nth-child(even) { background-color: #f2f2f2; }
                    .no-results { text-align: center; color: red; }
                    #sampleImage{max-height: 100px;}
                }
                </style>`;

            // Open preview in a new window instead of replacing the current document body
            var printWindow = window.open('', 'ReportPreview', 'width=1000,height=800');
            printWindow.document.open();
            printWindow.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Report Preview</title>');
            // copy current stylesheets and <style> blocks (avoid copying scripts)
            Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).forEach(function(node) {
                printWindow.document.write(node.outerHTML);
            });
            printWindow.document.write(printStyles);
            printWindow.document.write('</head><body>');
            printWindow.document.write('<div class="report-container">');
            printWindow.document.write(printContents);
            printWindow.document.write('</div>');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            // Wait briefly for resources (images/fonts) to load, then trigger print
            setTimeout(function() {
                printWindow.print();
            }, 500);
        });

       
    });
    </script>
