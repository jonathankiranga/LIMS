# Sales Quotation Discount Feature Documentation

## Overview
The Sales Quotation module now supports discount calculation at two levels:
1. **Line-Level Discount** - Discount percentage applied to individual line items
2. **Header-Level Discount** - Discount percentage applied to the entire quote subtotal

## Implementation Details

### Database Schema Changes
Two new columns have been added to support discounts:

1. **SalesLine.LineDiscountPercent** (DECIMAL(5,2))
   - Stores the discount percentage for each line item
   - Default value: 0
   - Applied to the net amount (before VAT) for each line

2. **SalesHeader.QtyDiscount** (DECIMAL(5,2))
   - Stores the header-level discount percentage
   - Default value: 0
   - Applied to the subtotal after all line discounts

### Database Migration
To set up the discount columns, run the migration script:

```bash
mysql -u root -p smarternowerp < sql/migration_add_discount_columns.sql
```

Or manually execute in your database client:

```sql
ALTER TABLE SalesLine 
ADD COLUMN LineDiscountPercent DECIMAL(5,2) DEFAULT 0 
AFTER TAT;

ALTER TABLE SalesHeader
ADD COLUMN QtyDiscount DECIMAL(5,2) DEFAULT 0;
```

## Usage

### Entering a Sales Quotation with Discounts

1. **Line Item Discount**
   - In the SalesQuotation.php entry form, fill in the "Discount (%)" field
   - This discount percentage will be applied to that specific line item
   - The discount is calculated as: `Line Discount Amount = Net Amount × (Discount % / 100)`

2. **Header-Level Discount**
   - In the quote header section, fill in the "Discount (%)" field
   - This discount is applied to the subtotal (after line discounts)
   - The discount is calculated as: `Header Discount Amount = Subtotal × (Discount % / 100)`

### Calculation Logic

The discount calculation follows this sequence:

```
For Each Line Item:
1. Calculate base net amount: UnitPrice × Quantity
2. Apply line discount: Net Amount = Net Amount × (1 - LineDiscount% / 100)
3. Calculate VAT: VAT = Net Amount × (VAT Rate / 100)

After All Lines:
4. Sum all net amounts → Subtotal
5. Apply header discount: Final Net = Subtotal × (1 - HeaderDiscount% / 100)
6. Add VAT to final net amount → Total Invoice Amount
```

## Files Modified

### 1. SalesQuotation.php
- Added line discount input field in the entry window
- Passes `LineDiscountPercent` to the quote items

### 2. transactions/poscart.inc (QUOTES class)
- Modified `Getitems()` method to accept and store `discountpercent`
- Updated `showtable()` to apply line-level discounts in display
- Header-level discount is read from `$_POST['DiscountPercent']`

### 3. transactions/Quotereadonly.inc
- Modified INSERT statement to save `LineDiscountPercent` to SalesLine table
- Reads discount value from `$_SESSION['sales_orders'][$lineno]['discountpercent']`
- Header-level discount is saved to SalesHeader.QtyDiscount by SalesQuotation.php (line 87)

### 4. PDFPrintSalesQuote.php
- Modified SQL query to retrieve discount percentages from database:
  - `LineDiscountPercent` from SalesLine
  - `QtyDiscount` from SalesHeader
- Updated line items display to show discount column
- Enhanced totals calculation:
  - Shows subtotal before discounts
  - Displays line discount amounts (if any)
  - Displays header discount percentage and amount (if any)
  - Shows final total after all discounts applied

## Values Not Hard-Coded

All discount values are dynamically sourced from:
- **User Input**: Discount percentages entered in the quote entry form
- **Session Storage**: Temporary storage in `$_SESSION['sales_orders']`
- **Database**: Persisted in SalesLine.LineDiscountPercent and SalesHeader.QtyDiscount
- **PDF Display**: Values retrieved from the database for PDF generation

## Example Calculation

```
Line Item 1:
  Unit Price: $100.00
  Quantity: 2
  Net Amount: $200.00
  Line Discount: 10%
  Discounted Net: $180.00
  VAT (15%): $27.00
  Line Total: $207.00

Line Item 2:
  Unit Price: $50.00
  Quantity: 1
  Net Amount: $50.00
  Line Discount: 0%
  Discounted Net: $50.00
  VAT (15%): $7.50
  Line Total: $57.50

Subtotal (after line discounts): $230.00
Header Discount: 5%
Header Discount Amount: -$11.50
Final Net Amount: $218.50
Total VAT: $34.50
TOTAL INVOICE: $253.00
```

## Testing

To test the discount feature:

1. Create a new sales quotation
2. Add a line item and set a discount percentage (e.g., 10%)
3. Click "Re-Calculate" to see the discount applied
4. Set a header-level discount (e.g., 5%)
5. Save the quote
6. Print the PDF and verify:
   - Line discount is shown in the PDF table
   - Discount amounts are calculated correctly
   - Final total reflects both line and header discounts

## Backward Compatibility

- The discount columns default to 0, so existing quotes without discounts will continue to work
- PDF generation gracefully handles missing discount columns using IFNULL()
- No hard-coded values are used; all discounts are user-specified
