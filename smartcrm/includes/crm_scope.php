<?php

/* Shared "My records" scope for the Smarternow CRM modules.
 *
 * The dashboard, leads, opportunities and communications screens are each
 * loaded into their own iframe, so the scope is kept in a cookie rather than
 * a query string: a single choice then applies across every screen without
 * having to be re-selected on each one.
 *
 * Recognised values are the task scope vocabulary used by the kanban
 * (see api.php listTasks) plus the plain ownership toggle:
 *
 *   my, mine, me, my-stalled, my-due  -> scope to the logged in user
 *   all, stalled, due                 -> no ownership restriction
 *   anything else / unset             -> scope to the logged in user
 *
 * Unrecognised values fall back to "my" rather than "all" so that a malformed
 * request hides records instead of exposing them.
 *
 * Records with a NULL or empty owner stay visible to everybody, so unclaimed
 * leads and opportunities and contacts created before ownership was recorded
 * do not silently disappear.
 */

if (!defined('CRM_SCOPE_COOKIE')) {
    define('CRM_SCOPE_COOKIE', 'crm_scope');
}

function crm_user_id()
{
    return isset($_SESSION['UserID']) ? (string)$_SESSION['UserID'] : '';
}

/* The raw scope value, from the request if given, else the cookie, else 'my'.
 * Retains the day-window variants so a kanban scope survives the round trip. */
function crm_scope_raw()
{
    if (isset($_POST['scope'])) {
        return strtolower(trim((string)$_POST['scope']));
    }
    if (isset($_GET['scope'])) {
        return strtolower(trim((string)$_GET['scope']));
    }
    if (isset($_COOKIE[CRM_SCOPE_COOKIE])) {
        return strtolower(trim((string)$_COOKIE[CRM_SCOPE_COOKIE]));
    }
    return 'my';
}

function crm_scope_is_mine($scopeRaw)
{
    return in_array(
        strtolower(trim((string)$scopeRaw)),
        array('my', 'mine', 'me', 'my-stalled', 'my-due'),
        true
    );
}

function crm_scope()
{
    $scopeRaw = crm_scope_raw();

    if (crm_scope_is_mine($scopeRaw)) {
        return 'my';
    }

    return in_array($scopeRaw, array('all', 'stalled', 'due'), true) ? 'all' : 'my';
}

/* The user id to restrict to, or '' when ownership should not restrict. */
function crm_scope_user()
{
    return crm_scope() === 'my' ? crm_user_id() : '';
}

/* Condition matching records owned by $user, or unowned when asked.
 * Returns '' when there is nothing to restrict on. */
function crm_owner_clause($user, $column, $includeUnassigned = true)
{
    $user = (string)$user;
    if ($user === '' || $column === '') {
        return '';
    }
    $safe = DB_escape_string($user);
    $clause = $column . " = '" . $safe . "'";
    if ($includeUnassigned) {
        $clause = '(' . $clause
            . ' OR ' . $column . ' IS NULL'
            . " OR " . $column . " = '')";
    }
    return $clause;
}

/* Condition matching records owned by the current user, or unowned.
 * Returns '' when ownership should not restrict the query. */
function crm_scope_owner_clause($column)
{
    return crm_owner_clause(crm_scope_user(), $column, true);
}

/* Condition matching records owned by the current user only. */
function crm_scope_owner_strict_clause($column)
{
    return crm_owner_clause(crm_scope_user(), $column, false);
}

/* Assembles a WHERE clause from condition fragments, or '' when there are none. */
function crm_scope_where(array $conditions)
{
    $kept = array();
    foreach ($conditions as $condition) {
        if ($condition !== '' && $condition !== null) {
            $kept[] = $condition;
        }
    }
    return empty($kept) ? '' : ' WHERE ' . implode(' AND ', $kept);
}

/* <option> markup for a My/All toggle, with the active scope preselected. */
function crm_scope_options($mineLabel, $allLabel)
{
    $current = crm_scope();
    $html  = '<option value="my"' . ($current === 'my' ? ' selected' : '') . '>'
        . htmlspecialchars($mineLabel, ENT_QUOTES) . '</option>';
    $html .= '<option value="all"' . ($current === 'all' ? ' selected' : '') . '>'
        . htmlspecialchars($allLabel, ENT_QUOTES) . '</option>';
    return $html;
}

/* Client side helper that persists a new scope and reloads.
 * $reload may be a URL string, or false to stay on the current page. */
function crm_scope_js()
{
    return "function setCrmScope(value, reload) {\n"
        . "    document.cookie = '" . CRM_SCOPE_COOKIE . "=' + encodeURIComponent(value)"
        . " + '; path=/; max-age=31536000; SameSite=Lax';\n"
        . "    if (reload !== false) { window.location.href = reload || window.location.href; }\n"
        . "}";
}
