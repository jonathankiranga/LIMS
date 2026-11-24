<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sample Registration</title>
    <link href="css/sampleregistration.css" rel="stylesheet">
    <link href="css/typing.css" rel="stylesheet">
    <link href="css/select2.min.css" rel="stylesheet" type="text/css"/>
</head>
<body class="edit-registration-page">
    <main class="edit-registration-shell">
        <section class="edit-registration-card">
            <header class="edit-registration-heading">
                <div>
                    <p class="eyebrow">LABORATORY INFORMATION SYSTEM</p>
                    <h1>Edit Sample Registration</h1>
                    <p class="heading-description">Select an existing batch, review its sample records and save the required changes.</p>
                </div>
                <div class="heading-mark" aria-hidden="true"><i class="fas fa-flask"></i></div>
            </header>

            <form id="labform" enctype="multipart/form-data">
                <section class="registration-section">
                    <div class="section-heading">
                        <div>
                            <h2>Registration details</h2>
                            <p>Batch and client information</p>
                        </div>
                        <span class="section-step">01</span>
                    </div>

                    <div class="registration-fields">
                        <div class="field-group field-group-wide">
                            <label for="documentno">Batch reference</label>
                            <select name="documentno" id="documentno" class="form-control-sm">
                                <option value="">Select registration batch</option>
                            </select>
                            <input type="hidden" name="HeaderID" id="HeaderID">
                            <input type="hidden" name="batchno" id="batchnoID">
                        </div>

                        <div class="field-group">
                            <label for="date">Registration date</label>
                            <input tabindex="1" type="date" name="date" id="date" class="form-control-sm" required>
                        </div>

                        <div class="field-group">
                            <label for="samplingdate">Sampling date</label>
                            <input type="date" name="samplingdate" id="samplingdate" class="form-control-sm" required>
                        </div>

                        <div class="field-group field-group-wide">
                            <label for="CustomerName">Client account</label>
                            <input tabindex="4" type="text" name="CustomerName" id="CustomerName" class="form-control-sm"
                                placeholder="Search a customer name" autocomplete="off"
                                autocapitalize="none" autocorrect="off" spellcheck="false"
                                onkeyup="handleCustomerNameInput(event)" required>
                            <input type="hidden" name="CustomerID" id="CustomerID">
                        </div>

                        <div class="field-group">
                            <label for="sampledby">Sampled by</label>
                            <input type="text" name="sampledby" id="sampledby" class="form-control-sm" required>
                        </div>

                        <div class="field-group">
                            <label for="SamplingMethod">Sampling method</label>
                            <input type="text" name="SamplingMethod" id="SamplingMethod" class="form-control-sm" required>
                        </div>

                        <div class="field-group field-group-wide">
                            <label for="Orderno">Customer LPO number</label>
                            <input type="text" name="Orderno" id="Orderno" class="form-control-sm" required>
                        </div>
                    </div>
                    <input type="hidden" name="tablecount" id="tablecount" value="0">
                </section>

                <section class="registration-section sample-section">
                    <div class="section-heading">
                        <div>
                            <h2>Registered samples</h2>
                            <p>Review sample details and update the relevant fields. Existing sample and test identifiers are retained.</p>
                        </div>
                        <span class="section-step">02</span>
                    </div>

                    <div class="registration-summary" id="registrationsumary">
                        <span>Rows: 0</span><span>Total samples: 0</span>
                    </div>

                    <div id="sampleTableContainer" class="sample-table-scroll">
                        <table class="table table-bordered table-hover table-sm align-middle" id="sampleTable">
                            <thead>
                                <tr>
                                    <th>Standard name</th>
                                    <th>Matrix package</th>
                                    <th>Samples</th>
                                    <th>Standard kit units</th>
                                    <th>Product batch no.</th>
                                    <th>Batch size</th>
                                    <th>Date of manufacture</th>
                                    <th>Date of expiry</th>
                                    <th>Chilled expiry</th>
                                    <th>Frozen expiry</th>
                                    <th>Sample picture</th>
                                    <th>Sample source</th>
                                    <th>Sample name</th>
                                    <th>Sample method</th>
                                    <th>Sample condition</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="sampleRows"></tbody>
                        </table>
                    </div>
                    <div id="samplehtml"></div>
                </section>

                <footer class="registration-actions">
                    <p>Saving updates the registration and its existing sample records. Existing laboratory results are protected.</p>
                    <button type="submit" id="mypostid" name="post" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save changes
                    </button>
                </footer>
            </form>
        </section>
    </main>

    <script src="js/select2.min.js" type="text/javascript"></script>
    <script src="js/upatesampleregistration.js" type="text/javascript"></script>
</body>
</html>
