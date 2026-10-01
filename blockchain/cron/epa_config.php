<?php
/**
 * Shared configuration for the EPA limits automation pipeline.
 */

// The two live EPA pages that actually contain MCL/SMCL tables.
// There is no clean EPA JSON/CSV feed for this data - these are the
// canonical HTML pages EPA itself publishes and maintains.
define('EPA_NPDWR_URL', 'https://www.epa.gov/ground-water-and-drinking-water/national-primary-drinking-water-regulations');
define('EPA_SMCL_URL', 'https://www.epa.gov/sdwa/secondary-drinking-water-standards-guidance-nuisance-chemicals');

define('EPA_USER_AGENT', 'Mozilla/5.0 (compatible; LabLimitsSync/1.0; +internal-water-quality-system)');

define('EPA_DATA_DIR', __DIR__ . '/../data');
define('EPA_RAW_CACHE', EPA_DATA_DIR . '/epa_limits_raw.json');
define('EPA_FINAL_CACHE', EPA_DATA_DIR . '/epa_limits_cache.json');
define('EPA_OVERRIDES_FILE', __DIR__ . '/epa_limits_overrides.json');

define('EPA_LOG_DIR', __DIR__ . '/../logs');
