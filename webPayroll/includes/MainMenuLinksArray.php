<?php
/* $Id: MainMenuLinksArray.php 6190 2013-08-12 02:12:02Z rchacon $*/
$ModuleLink = array('HM',
                    'HR',
                    'system');

$ReportList = array('HM'=>'home',
                    'HR'=>'payroll',
                    'system'=>'sys');

/*The headings showing on the tabs accross the main index used also in WWW_Users for defining what should be visible to the user */
$ModuleList = array(_('Resource Management'),
                    _('PayRoll Management'),
                    _('Application Setup '));


/* webERP menus with Captions and URLs. */
$MenuItems['HM']['Transactions']['Caption'] = array( _('Employee Registration'),
                                                     _('Edit Employee Details'),
                                                     _('Salary Increment Module'));

$MenuItems['HM']['Transactions']['URL'] = array('/prlhr.php',
                                                 '/prlhrlistview.php',
                                                 '/prlsalaryinrement.php');

$MenuItems['HM']['Reports']['Caption'] = array(_('Print Birth Days Reminder'),
                                               _('Print Leave Schedule by date'),
                                               _('Print HR customized categories'));

$MenuItems['HM']['Reports']['URL'] = array('/PDFbirthdayreminder.php',
                                           '/PDFleaveschedule.php',
                                           '/PDFcustomreports.php');

$MenuItems['HM']['Maintenance']['Caption'] = array(_('Establishment'),
                                                   _('Create Positions'),
                                                   _('Create Qualifications'),
                                                   _('Create Departments'),
                                                   _('Human Resource Categories'),
                                                   _('Salary scales'),
                                                   _('Working Days'));

$MenuItems['HM']['Maintenance']['URL'] = array('/SelectEstablishment.php',
                                              '/prlpositions.php',
                                              '/prlqualification.php',
                                              '/prldepartments.php',
                                              '/prlproperties.php',
                                              '/prlsalaryscale.php',
                                              '/daysoftheweek.php');

/* Leave Management - Enhanced Kenyan Statutory Leave */
$MenuItems['HM']['Leave Management']['Caption'] = array(_('Request for Leave'),
                                                        _('Leave Approval'),
                                                        _('Leave Balances'),
                                                        _('Leave Types Setup'),
                                                        _('Kenya Holidays'),
                                                        _('Leave Carryover'),
                                                        _('Leave Encashment'),
                                                        _('Leave Reports'),
                                                        _('Escalation Rules'),
                                                        _('Notifications'));

$MenuItems['HM']['Leave Management']['URL'] = array('/prlleaveapplication.php',
                                                   '/prlleaveapproval.php',
                                                   '/prlleavebalances.php',
                                                   '/prlleavetypes.php',
                                                   '/prlkenyaholidays.php',
                                                   '/prlleavecarrovers.php',
                                                   '/prlleaveencashment.php',
                                                   '/prlleavereports.php',
                                                   '/prlleaveescalations.php',
                                                   '/prlleavenotifications.php');


$MenuItems['HR']['Transactions']['Caption'] = array(_('Credit/loans Control'),
                                                    _('SACCO and Savings Control'),
                                                    _('Time/Absent Supervisor'),
                                                    _('Post Miscelleneos Payroll Items'),
                                                    _('Calculate Payroll'),
                                                    _('CSV export payroll file'));

$MenuItems['HR']['Transactions']['URL'] = array('/prlcreditloans.php',
                                                '/prlsaccosavings.php',
                                                '/prltimesheet.php',
                                                '/prlmiselleneous.php' ,
                                                '/prlgenerateslips.php',
                                                '/Employeenetpay.php');

$MenuItems['HR']['Reports']['Caption'] = array(_('Loan Deductions Printing'),
                                               _('SACCO Deductions Printing'),
                                               _('Pay-Slip $ Payroll Spread Reports'),
                                               _('P9 P.A.Y.E. Report'),
                                               _('P10 P.A.Y.E. Report'),
                                               _('N.H.I.F. Report'),
                                               _('N.S.S.F. Report'),
                                               _('Print Other Payroll Item Deductions'),
                                               _('Audit on Changes of Basic Pay'),
                                               _('Audit on Changes of Allowances/Deductions'),
                                               _('Audit on Changes of Loans'),
                                               _('Audit on New Staff added'),
                                               _('Audit on Staff Terminated'));


$MenuItems['HR']['Reports']['URL'] = array('/prl_pdf_loans.php',
                                            '/prl_pdf_sacco.php',
                                            '/prlmasterroll.php', 
                                            '/prlpaye.php',
                                            '/prlpayePten.php',
                                            '/prlnhif.php',
                                            '/prlnssf.php', 
                                            '/prl_pdf_byproducts.php',
                                            '/PDFauditbasicpay.php',
                                            '/PDFauditallowances.php',
                                            '/PDFauditloans.php',
                                            '/PDFauditnewemployees.php',
                                            '/PDFauditbasicpay.php?terminate=1');

 
$MenuItems['HR']['Maintenance']['Caption'] = array(_('Create Banks'),
                                                   _('Create Banks Branch'),
                                                   _('Create Deductions and Allowances items'),
                                                   _('Define Payroll Salaries'));

$MenuItems['HR']['Maintenance']['URL'] = array( '/prlbanks.php',
                                                '/prlbankbranches.php',
                                                '/prlpayrollines.php',
                                                '/prlsalary.php' 
                                              );
 

$MenuItems['system']['Transactions']['Caption'] = array(_('Company Preferences'),
                                                        _('Users Maintenance'),
                                                        _('Maintain Security Tokens'),
                                                        _('Access Permissions Maintenance'),
                                                        _('Page Security Settings'),
                                                        _('System Parameters'),
                                                        _('SMTP Mail Server Details'));

$MenuItems['system']['Transactions']['URL'] = array('/CompanyPreferences.php',
                                                    '/WWW_Users.php',
                                                    '/SecurityTokens.php',
                                                    '/WWW_Access.php',
                                                    '/PageSecurity.php',
                                                    '/SystemParameters.php',
                                                     '/SMTPServer.php');

 $MenuItems['system']['Reports']['Caption'] = array(_('View Audit Trail'));

 $MenuItems['system']['Reports']['URL'] = array('/AuditTrail.php');


 $MenuItems['system']['Maintenance']['Caption'] = array(_('Establishment Maintenance'),
                                                       _('Job Group Maintenance'),
                                                       _('Position Maintenance'),
                                                       _('Qualification Maintenance'),
                                                       _('Department Maintenance'),
                                                       _('Create system Authourisation'));


 $MenuItems['system']['Maintenance']['URL'] = array('/SelectEstablishment.php',
                                                    '/prljobgroup.php',
                                                    '/prlpositions.php',
                                                    '/prlqualification.php',
                                                    '/prldepartments.php',
                                                    '/PrlAuthorizers.php');






?>
