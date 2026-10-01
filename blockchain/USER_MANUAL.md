# LIMS User Training Manual

This manual is written directly to you.
It covers only day-to-day laboratory workflows and excludes internal system details.

## What this manual includes
- Sample registration through approval
- Sub-contractor assignment
- Chain of custody and sample disposal
- Operational reporting
- Predictive calibration and control sample entry

## What this manual excludes
- Role and user administration
- System configuration
- Technical setup pages
- Internal links and backend routes

## Menu items currently unavailable in this release
- Schedule Samples
- Perform QA Checks
- View QA Reports

## 1) Login and open Home
Menu path: `Login` then `Home`
1. Enter your username and password.
2. Click `Login`.
3. Confirm your user name and dashboard banner details are visible.

## 2) Register a New Sample
Menu path: `Sample Management > Register a New Sample`
1. Enter the registration date.
2. Confirm batch reference is auto-filled.
3. Search and select the client account.
4. Fill sampled by, sampling method, sampling date, and customer LPO number.
5. Click `Add Sample`.
6. For each sample row, fill required fields:
   - Standard name
   - Number of samples
   - Standard kit units
   - Product batch number
   - Batch size
   - Manufacture date
   - Expiry date
7. Add optional details like matrix package, sample image, and sample source.
8. If needed, create a new customer using `Create New Customer`.
9. Click `SAVE`.
10. Confirm the success message.

## 3) Edit Sample Registration
Menu path: `Sample Management > Edit Sample Registration`
1. Select the existing batch reference.
2. Wait for header and sample rows to load.
3. Update the fields you need to change.
4. Add or remove rows as needed.
5. Click `SAVE`.
6. Re-open the same batch and confirm changes were applied.

## 4) Receive Sample by Department
Menu path: `Sample Management > Receives Sample By department`
1. Review the pending sample list.
2. Click `Receive Sample` on the row you want.
3. In the popup, confirm sample ID and sample name.
4. Set department, arrival condition, and optional remarks/location.
5. Click `Receive`.
6. Confirm the success message and list refresh.

## 5) Enter Chemical Test Results
Menu path: `Sample Management > Chemical Samples Tests`
1. Filter the list if needed.
2. For each row, choose result type.
3. Enter the result value.
4. Click `Save`.
5. Repeat until all pending rows are completed.

## 6) Enter Microbiological Test Results
Menu path: `Sample Management > Microbiological Samples Tests`
1. Filter the list if needed.
2. For each row, choose result type.
3. Enter the result value.
4. Click `Save`.
5. Confirm saved rows update visually.

## 7) Review Test Results
Menu path: `Sample Management > Review Test Results`
1. Search for the result row you want to review.
2. Click `Review`.
3. Check the sample and parameter details.
4. Enter or adjust quantitative, qualitative, or range result as needed.
5. Select approval status.
6. Click `Submit Approval`.
7. Confirm the list refreshes after success.

## 8) Approve Chemical Results
Menu path: `Sample Management > Approve Chemical`
1. Search for the reviewed chemical result.
2. Click `Approve`.
3. Validate the result and standard limit context.
4. Select approval status.
5. Click `Submit Approval`.
6. Confirm success and refresh.

## 9) Approve MicroBiological Results
Menu path: `Sample Management > Approve MicroBiological`
1. Search for the reviewed microbiological result.
2. Click `Approve`.
3. Validate the result and standard limit context.
4. Select approval status.
5. Click `Submit Approval`.
6. Confirm success and refresh.

## 10) Assign Tests to a Sub-Contractor
Menu path: `Environmental Monitoring > Record Enviromental Factors`
1. Open the sample test list.
2. If needed, create a new lab sub-contractor first.
3. Click `Select Sub-Contractor` for the test you want to assign.
4. Choose a subcontractor in the allocation popup.
5. Drag tests into the assignment area.
6. Click `Save Allocation`.
7. Confirm success and refresh.

## 11) Log Chain of Custody
Menu path: `Chain of Custody > Log Custody Transfers`
1. Search and select the sample row.
2. Click `Book Custody`.
3. Fill handler name, action, location, and notes.
4. Click `Update Custody`.
5. Optionally click `View Custody Trail` to verify the history.

## 12) Dispose of Samples
Menu path: `Chain of Custody > Dispose of Samples`
1. Search and select the sample row.
2. Click `Dispose Samples`.
3. Enter the disposal reason.
4. Click `Dispose`.
5. Confirm the success message.

## 13) Quick Lab Report
Menu path: `Reports > Quick Lab Report`
1. Open the record selector.
2. Select customer or batch.
3. Verify header information and result rows.
4. Click `Print` to produce the report.

## 14) Certificate of Assesment
Menu path: `Reports > Certificate of Assesment`
1. Search the approved sample list.
2. Click the PDF button for the sample.
3. Choose report style in the popup.
4. Wait for generation to complete.
5. Review the PDF output.

## 15) Sample Summary by Date
Menu path: `Reports > Sample Summary by date`
1. Select `From` and `To` dates.
2. Click `Generate Report`.
3. Wait for processing to complete.
4. Review the generated PDF.

## 16) Predictive Calibration
Menu path: `Predictive Analysis > Predictive Calibration`
1. Select a machine.
2. Review historical and predicted deviation trends.
3. Use the output to plan upcoming calibration work.

## 17) Control Samples
Menu path: `Predictive Analysis > Control Samples`
1. Add a machine if it does not exist yet.
2. Select equipment ID.
3. Enter sample name, known value, and measured value.
4. Click `Submit Test Result`.
5. Confirm the success message.

