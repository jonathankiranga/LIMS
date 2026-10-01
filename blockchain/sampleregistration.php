<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Registration</title>
    <link href="css/sampleregistration.css" rel="stylesheet">
    <link href="css/typing.css" rel="stylesheet"> 
    <script src="js/fetchTransactionData.js"  type="text/javascript"></script>
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .form-group label {
            font-weight: 500;
            font-size: 0.875rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
        }
        .form-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            align-items: center;
        }
        .customer-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .customer-form-grid {
                grid-template-columns: 1fr;
            }
        }
        .sample-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .sample-grid > div {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .sample-grid label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        .sample-grid .full-width {
            grid-column: span 3;
        }
        @media (max-width: 768px) {
            .sample-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .sample-grid .full-width {
                grid-column: span 2;
            }
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
    </div>
    <div class="card-body">
        <form id="labform" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="date">DATE:</label>
                    <input tabindex="1" type="date" name="date" id="date" class="form-control-sm" size="11" maxlength="11" autofocus="autofocus" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <label for="documentno">Batch Ref:</label>
                    <input type="text" name="documentno" id="documentno" class="form-control-sm" size="10" readonly="readonly">
                </div>
                <div class="form-group">
                    <label for="CustomerName">Client Account:</label>
                    <input tabindex="4" type="text" name="CustomerName" id="CustomerName" class="form-control-sm" value="" placeholder="Search a customer name" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" onkeyup="handleCustomerNameInput(event)">
                    <input type="hidden" name="CustomerID" id="CustomerID">
                    <input type="hidden" name="tablecount" id="tablecount">
                </div>
                <div class="form-group">
                    <label for="sampledby">Sampled By:</label>
                    <input type="text" name="sampledby" id="sampledby" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <label for="SamplingMethod">Sampling Method:</label>
                    <input type="text" name="SamplingMethod" id="SamplingMethod" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <label for="samplingdate">Sampling Date:</label>
                    <input type="date" name="samplingdate" id="samplingdate" class="form-control-sm" size="11" maxlength="10" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <label for="Orderno">Customer LPO No:</label>
                    <input type="text" name="Orderno" id="Orderno" class="form-control-sm" value="" size="20" autocapitalize="none" autocorrect="off" spellcheck="false">
                </div>
                <div class="form-group">
                    <label for="quoteno">Quotation:</label>
                    <div class="d-flex align-items-center">
                        <input type="text" name="quoteno" id="quoteno" class="form-control-sm" value="" size="20" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false" placeholder="Search quotation" onkeyup="handleQuoteInput(event)">
                        <button type="button" id="findQuoteBtn" class="btn btn-outline-primary btn-sm" onclick="searchQuotes('')">Find Quotation</button>
                    </div>
                </div>
            </div>

            <hr>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addSampleRow()">
                <i class="fa fa-plus"></i> Add Sample
            </button>

            <div id="sampleTableContainer" class="table-responsive">
                <table class="table table-bordered table-hover table-sm align-middle table-responsive" id="sampleTable">
                    <thead class="table-light">
                        <tr>
                            <th>Standard Name</th>
                            <th>Matrix Package</th>
                            <th>Number of Samples</th>
                            <th>Standard Kit Units</th>
                            <th>Product Batch No</th>
                            <th>Batch Size</th>
                            <th>Date of Manufacture</th>
                            <th>Date of Expiry</th>
                            <th>Chilled Date of Expiry</th>
                            <th>Frozen Date of Expiry</th>
                            <th>Picture of Sample</th>
                            <th>Sample Source</th>
                            <th>Sample Name</th>
                            <th>Sample Method</th>
                            <th>Condition of Sample</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="sampleRows"></tbody>
                </table>
            </div>
            <br>
            <hr>
            <p><div id="registrationsumary"></div></p>

            <div id="samplehtml"></div>

            <hr>
            <div>
                <button type="submit" id="mypostid" name="post" class="btn btn-primary"><i class="fas fa-save"></i>🚀 SAVE</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <form id="customerForm">
                    <div class="customer-form-grid">
                        <div class="form-group">
                            <label for="customer">Name</label>
                            <input type="text" name="customer" id="customer" maxlength="50" required="required">
                        </div>
                        <div class="form-group">
                            <label for="company">Address</label>
                            <input type="text" name="company" id="company" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="postcode">Address 2</label>
                            <input type="text" name="postcode" id="postcode" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select name="country" id="countrySelect"></select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Telephone No</label>
                            <input type="text" name="phone" id="phone" maxlength="15">
                        </div>
                        <div class="form-group">
                            <label for="altcontact">Alt Contact</label>
                            <input type="text" name="altcontact" id="altcontact" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="text" name="email" id="email" maxlength="100" pattern="[a-z0-9!#$%&amp;'*+/=?^_{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*">
                        </div>
                        <div class="form-group">
                            <label for="creditlimit">Discount Rate</label>
                            <input type="text" class="integer" name="creditlimit" id="creditlimit" maxlength="2">
                        </div>
                        <div class="form-group">
                            <label for="curr_cod">Customer Currency:</label>
                            <select name="curr_cod" id="curr_cod" required="required">
                                <option value="EUR">Euro</option>
                                <option value="GBP">Pounds</option>
                                <option value="KES" selected>Kenyan Shillings</option>
                                <option value="USD">US Dollars</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="inactive">Block Account</label>
                            <select name="inactive" id="inactive">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="middlen">PIN</label>
                            <input type="text" name="middlen" id="middlen" maxlength="15">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save Customer</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
fetchTransactionData('GetTempLabrefNo','10');

$('#new_customer').click(function() {
    const modal = new bootstrap.Modal(document.getElementById('modal'), { backdrop: 'static', keyboard: false});
    modal.show();
});
</script>
<script src="js/sampleregistration.js?v=<?php echo filemtime(__DIR__ . '/js/sampleregistration.js'); ?>" type="text/javascript"></script>
</body>
</html>