# Import sinh viên and catalog workbook format

The Student Management page generates an `.xlsx` template for the selected
dataset. Import accepts only `.xlsx` workbooks whose first worksheet has the
exact header names and order listed below. Column guessing, localized header
aliases, `.xls`, formulas, and external workbook links are not supported.

| Import order | Dataset | Exact first-row headers | Foreign-key mapping |
| --- | --- | --- | --- |
| 1 | `KHOA` | `MA_KHOA`, `TEN_KHOA` | None |
| 2 | `NGANH` | `MA_NGANH`, `TEN_NGANH`, `MA_KHOA` | `MA_KHOA` → `KHOA.MA_KHOA` |
| 3 | `LOP` | `MA_LOP`, `MA_NGANH` | `MA_NGANH` → `NGANH.MA_NGANH` |
| 4 | `SINHVIEN` | `MA_SV`, `HOTEN`, `NIEN_KHOA`, `MA_LOP` | `MA_LOP` → `LOP.MA_LOP` |

Codes are text identifiers, not display names. `NIEN_KHOA` is stored as the
SQL Server `INT` value exactly as supplied. This implementation does not
interpret or convert values such as `14` or `15` into four-digit years.
Codes and names must be text cells in Excel; `NIEN_KHOA` may be a text or
numeric integer cell within the SQL Server `INT` range. Names preserve
Vietnamese Unicode.

Upload is limited to 15 MiB and 5,000 data rows. Empty required values, invalid
codes, overlong values, non-integer/out-of-range `NIEN_KHOA`, missing foreign
keys, malformed workbooks, and rows
with formula cells are rejected. Existing or repeated unique codes (and
duplicate unique faculty/major names) are marked as duplicates and skipped.
Any validation error blocks the entire import; after confirmation, valid
non-duplicate rows are inserted in one SQL Server transaction. A database
failure rolls the transaction back. Preview rows are held in the authenticated
PHP session for up to 30 minutes; the uploaded file remains in PHP's temporary
upload location and is not copied to application storage.

## Runtime prerequisites

- PHP `pdo_sqlsrv` and PHP `zip` extensions.
- PHP 8.1 or newer, with `mbstring`, `SimpleXML`, and `fileinfo`.
- PHP upload settings `upload_max_filesize` of at least `15M` and
  `post_max_size` of at least `16M`.
- Environment variables `CIM_SQLSERVER_HOST`, `CIM_SQLSERVER_DATABASE`,
  `CIM_SQLSERVER_USERNAME`, and `CIM_SQLSERVER_PASSWORD`; optional TLS
  variables `CIM_SQLSERVER_ENCRYPT` (`yes`/`no`, defaults to `yes`) and
  `CIM_SQLSERVER_TRUST_CERTIFICATE` (`yes`/`no`, defaults to `no`).
- The surrounding authentication flow must establish the PHP session values
  `$_SESSION['authenticated'] = true` and `$_SESSION['role']` as `ADMIN` or
  `CTSV`. The student API rejects requests without that authenticated session.

This module reads the existing schema in `documents/SQL_Querysql.sql` and
does not create or alter database objects.
