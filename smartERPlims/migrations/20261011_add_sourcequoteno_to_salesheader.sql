-- Run this once against the accounting application's database.
-- Stores the quotation number used to create each sales order (documenttype = 10).
ALTER TABLE salesheader
    ADD COLUMN sourcequoteno VARCHAR(100) NULL DEFAULT NULL;

CREATE INDEX idx_salesheader_sourcequoteno
    ON salesheader (documenttype, sourcequoteno);
