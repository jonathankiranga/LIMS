# Kenyan Statutory Leave Management System - SmartNow Payroll

## Overview

This is an enhanced leave management module for SmartNow Payroll that complies with Kenyan Employment Act 2007 requirements. It adds comprehensive statutory leave tracking for Kenyan companies.

## Installation

### 1. Run Database Migration

Execute the SQL migration script to create the required tables:

```bash
mysql -u root -p webpayroll < SQL/leave_management_migration.sql
```

Or import `SQL/leave_management_migration.sql` via phpMyAdmin.

### 2. Access New Modules

Navigate to: **Resource Management → Leave Management**

## Features Implemented

### 1. Statutory Leave Types (Kenya Employment Act 2007)

| Leave Type | Days | Notes |
|------------|------|-------|
| Annual Leave | 28 working days | Minimum per Employment Act |
| Maternity Leave | 90 working days (paid) + 30 (unpaid) | Female employees only |
| Paternity Leave | 10 working days | Male employees only |
| Sick Leave (Full Pay) | 30 days | Requires medical certificate |
| Sick Leave (Half Pay) | 30 days | Requires medical certificate |
| Compassionate Leave | 10 days | Immediate family |
| Study Leave | 21 days | Professional development |

### 2. Core Modules

| Module | File | Description |
|--------|------|-------------|
| Leave Types Setup | `prlleavetypes.php` | Configure leave types with statutory settings |
| Kenya Holidays | `prlkenyaholidays.php` | Manage gazetted public holidays |
| Leave Balances | `prlleavebalances.php` | View/manage employee leave balances |
| Leave Application | `prlleaveapplication.php` | Submit leave requests with AJAX calculations |
| Leave Approval | `prlleaveapproval.php` | Multi-level approval workflow |
| Leave Carryover | `prlleavecarrovers.php` | Manage end-of-year carryover |
| Leave Encashment | `prlleaveencashment.php` | Process leave encashment |
| Leave Reports | `prlleavereports.php` | Summary, liability, and calendar reports |

### 3. Key Features

- **Working Days Calculator**: Automatically excludes weekends and public holidays
- **Pro-Rata Calculation**: Proportional leave for employees joining mid-year
- **Carryover Limits**: Configurable maximum carryover days (default: 7)
- **Medical Certificate Tracking**: Required for sick leave >3 days
- **Multi-Level Approval**: HOD → HR workflow
- **Leave Encashment**: Process unused leave payments (max 7 days per year)
- **Leave Liability Reports**: Financial reporting for accrued leave
- **Gender-Based Eligibility**: Maternity/Paternity leave restrictions
- **Service Period Requirements**: Minimum service before entitlement

### 4. Database Tables Created

1. `prlleavetypes` - Leave type configuration
2. `prlkenyaholidays` - Kenya gazetted holidays
3. `prlleavebalances` - Per-employee, per-type, per-year balances
4. `prlleaveapplications` - Leave application records
5. `prlleaveapprovaltrans` - Approval workflow tracking
6. `prlleavecarrovers` - Carryover tracking with expiry
7. `prlleaveencashments` - Encashment requests
8. `prlleavenotifications` - Email/SMS notifications queue
9. `prlleaveaudit` - Comprehensive audit trail
10. `prlleaveworkflows` - Approval workflow configuration
11. `prlweekends` - Working days configuration
12. Views: `prlv_leave_summary`, `prlv_active_leaves`

### 5. API Endpoints

Access via `AjaxLeaveCalculator.php`:

- `getLeaveBalance` - Get employee leave balances
- `calculateDates` - Calculate end date and return date
- `checkEligibility` - Check if employee is eligible for leave type
- `getProRata` - Calculate pro-rata leave entitlement
- `calculateWorkingDays` - Calculate working days between dates
- `getUpcomingHolidays` - List upcoming holidays
- `getApprovalChain` - Get approval chain for employee

## Configuration

### Leave Type Settings

| Setting | Description |
|---------|-------------|
| `default_days` | Annual entitlement |
| `paid_leave` | Whether leave is paid |
| `requires_medical_certificate` | Needs medical cert for sick leave |
| `allow_carryover` | Can unused days carry forward |
| `max_carryover_days` | Maximum days to carry over |
| `pro_rata_applicable` | Calculate proportionally for new employees |
| `encashable` | Can be encashed on termination |
| `min_service_months` | Minimum months before entitlement |
| `gender_applicable` | Male/Female/All employees |

### Kenya Gazetted Holidays

Pre-configured for 2024-2026:
- New Year's Day (Jan 1)
- Good Friday
- Easter Monday
- Madaraka Day (Jun 1)
- Mashujaa Day (Oct 20)
- Jamhuri Day (Dec 12)
- Christmas Day (Dec 25)
- Boxing Day (Dec 26)

## Usage

### 1. Initial Setup
1. Go to **Leave Management → Leave Types Setup** (defaults already configured)
2. Go to **Kenya Holidays** to verify/update public holidays
3. Go to **Leave Balances** → Click "Assign Leave to All Employees"

### 2. Leave Request Process
1. Employee goes to **Leave Management → Request for Leave**
2. Select leave type, dates (working days calculated automatically)
3. System checks balance and eligibility
4. Submit for approval

### 3. Approval Process
1. Approver goes to **Leave Management → Leave Approval**
2. Review application details
3. Approve or reject with comments
4. System updates balance automatically

### 4. Year-End Process
1. Go to **Leave Carryover** → Process Carryover
2. System automatically carries over up to max days
3. Excess days are forfeited

## Statutory Compliance Notes

Per **Kenya Employment Act 2007**:

1. **Annual Leave (Section 28)**: Minimum 28 working days per year
2. **Maternity Leave (Section 29)**: 90 working days with pay, up to 30 additional without pay
3. **Paternity Leave (Section 29A)**: 10 working days
4. **Sick Leave (Section 30)**: 30 days full pay, 30 days half pay per year
5. **Compassionate Leave**: Reasonable time for immediate family bereavement
6. **Leave Encashment**: Cannot forfeit earned annual leave (Section 31)

## Files Modified

- `includes/MainMenuLinksArray.php` - Added Leave Management menu
- `ExtFunc/humanresource.inc` - Added new leave functions

## Files Created

- `SQL/leave_management_migration.sql` - Database schema
- `prlleavetypes.php` - Leave type configuration
- `prlkenyaholidays.php` - Holiday management
- `prlleavebalances.php` - Balance management
- `prlleaveapplication.php` - Leave request form
- `prlleaveapproval.php` - Approval workflow
- `prlleavecarrovers.php` - Carryover management
- `prlleaveencashment.php` - Encashment processing
- `prlleavereports.php` - Reports module
- `AjaxLeaveCalculator.php` - AJAX API
- `README_LEAVE_MANAGEMENT.md` - This file

## Support

For issues or enhancements, please contact the development team.

---
**Version**: 1.0  
**Date**: April 2026  
**Compatible with**: SmartNow Payroll / webERP
