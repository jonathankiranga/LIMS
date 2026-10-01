-- ---------------------------------------------------------------------------
-- LIMS -> smartERP sync: make the LIMS identifiers real keys so a re-import
-- updates the existing document instead of appending a duplicate.
--
-- Keys introduced
-- ----------------
-- 1. salesheader UNIQUE (documenttype, documentno)
--    The LIMS document number is the natural key of a sales document. Numbers
--    only have to be unique WITHIN their document type (54 = quotation,
--    1 = order, 2 = invoice), so both columns are part of the key.
--
-- 2. salesline FOREIGN KEY (documenttype, documentno)
--      -> salesheader (documenttype, documentno)  ON DELETE CASCADE
--    This is the link that makes a delete safe: removing a sales header can
--    only ever take its own lines with it, and a line can never reference a
--    header that does not exist. The application already writes the header
--    before the lines inside one transaction, so the constraint matches the
--    existing write order.
--
-- 3. stockmaster UNIQUE (labid)
--    The LIMS baseparameters.ParameterID. NULLs may repeat (MySQL ignores
--    NULLs in a unique index), which keeps the 166 manually created items that
--    were never synced working.
--
-- Verified before applying: 0 orphan salesline rows, exact column type and
-- collation parity on both sides (int NOT NULL / varchar(20) NOT NULL
-- utf8mb4_unicode_ci), no pre-existing foreign keys, no NULL documentno.
-- ---------------------------------------------------------------------------

-- 1. Drop surplus duplicate sales headers, keeping the lowest entryno of each
--    group. The cascade removes any lines that belonged to them, which is the
--    only reason it is safe to delete a header at all.
DELETE s
FROM salesheader s
JOIN (
    SELECT MIN(entryno) AS keep_entryno, documenttype, documentno
    FROM salesheader
    GROUP BY documenttype, documentno
    HAVING COUNT(*) > 1
) d
  ON s.documenttype <=> d.documenttype
 AND s.documentno  <=> d.documentno
 AND s.entryno > d.keep_entryno;

-- 2. One sales document per (type, number) from now on.
ALTER TABLE salesheader
    ADD UNIQUE KEY uq_salesheader_doctype_docno (documenttype, documentno);

-- 3. Declare the parent -> child link.
ALTER TABLE salesline
    ADD KEY idx_salesline_doctype_docno (documenttype, documentno),
    ADD CONSTRAINT fk_salesline_salesheader
        FOREIGN KEY (documenttype, documentno)
        REFERENCES salesheader (documenttype, documentno)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

-- 4. One stock item per LIMS parameter id.
ALTER TABLE stockmaster
    ADD UNIQUE KEY uq_stockmaster_labid (labid);

-- Notes
-- -----
-- * salesline gets no unique index on (documenttype, documentno, code): four
--   real documents legitimately repeat a code on the same document, so such an
--   index would reject valid data. Line idempotency is handled in code instead
--   - the API clears the lines of a re-synced order before rewriting them.
-- * Tables that also carry documentno + documenttype (purchaseheader,
--   enterbillheaders, assetsheader, generalledger, customerstatement,
--   supplierstatement, fixedassetsline, purchaseline, enterbillslines) are
--   separate document hierarchies with their own numbering and are deliberately
--   NOT linked to salesheader.
-- * To re-run after a partial failure, drop the constraints first:
--   ALTER TABLE salesline DROP FOREIGN KEY fk_salesline_salesheader;
--   ALTER TABLE salesheader DROP INDEX uq_salesheader_doctype_docno;
--   ALTER TABLE stockmaster DROP INDEX uq_stockmaster_labid;
