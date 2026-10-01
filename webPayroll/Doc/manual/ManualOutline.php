<?php
/* $Id: ManualOutline.php 5450 2009-12-24 15:28:49Z icedlava $ */
/*
 * This is the outline of the webERP manual
**/
$TOC_Array = array (
'TableOfContents'   => array(
    'Introduction'      => array('Introduction', 'Dobetech Solutions webHR software'),
    'Requirements'      => array('Requirements',
                                 'Hardware requirements',
                                 'Software requirements'),
    'GettingStarted'    => array('Getting started',
                                 'Prerequisites',
                                 'Copying the PHP Scripts',
                                 'Creating the database',
                                 'Editing config.php',
                                 'Logging in for the first time',
                                 'Themes and GUI modification',
                                 'Setting up users'),
    'SecuritySchema'    => array('Security schema'),
    'CreatingNewSystem' => array('Creating a new system',
                                 'Running the Demonstration database',
                                 'Setting up a system',
                                 'Setting up Payroll By-products',
                                 'Importing From MS-Nav balances',
                                 'Setting up employees',
                                 'Entering employee balances'),
    'SystemConventions' => array('System Conventions','Navigating the menu','Reporting'),

    'Contracts'   => array('Contract Costing',
                                 'Contract costing overview',
                                 'Creating a new contract',
                                 'Selecting a contract',
                                 'Charging against contracts'),

    'ReportBuilder'     => array('SQL Report Writer',
                                 'Report writer introduction',
                                 'Reports administration',
                                 'Importing and exporting reports',
                                 'Editing, copying, renaming, reports',
                                 'Creating a new report - identification',
                                 'Creating a new report - page setup',
                                 'Creating a new report - Specifying database tables and links',
                                 'Creating a new report - specifying fields to retrieve',
                                 'Creating a new report - entering and arranging criteria',
                                 'Viewing reports'),

    'Multilanguage'     => array('Multilanguage',
                                 'Introduction to multilanguage',
                                 'Rebuild the system default language file',
                                 'Add a new language to the system',
                                 'Edit a language file header',
                                 'Edit a language file module'),
    'SpecialUtilities'  => array('Special utilities',
                                 'Reapply standard costs to sales analysis',
                                 'Change a customer code',
                                 'Change an inventory code',
                                 'Make stock locations',
                                 'Repost general ledger from period'),

    'NewScripts'=> array('Development - Foundations',
                                 'Directory structure',
                                 'session.inc',
                                 'header.inc',
                                 'footer.inc',
                                 'config.php',
                                 'PDFStarter.php',
                                 'Database abstraction - ConnectDB.inc',
                                 'DateFunctions.inc',
                                 'SQL_CommonFunctions.inc'),
    'APITutorial'  => array('API Tutorial'),
    'APIFunctions' => array('API Function reference'),
    'DevelopmentStructure' => array('Development Structure'),
    'Contributors' => array('Contributors - Acknowledgements')
    )
);

?>