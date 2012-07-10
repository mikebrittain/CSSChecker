<?php
/**
 * Find unused CSS selectors in code base.
 * 
 * @TODO - Search through template files for <style> declarations for additional 
 * selectors to verify.  This is not as useful if we spend some time to migrate
 * selectors to external stylesheets instead.
 * 
 * @TODO - Search for unused HTML rules
 */

if (!isset($argv[1])) {
    echo "Missing parameter 1: CSS top-level path or path to a single CSS file (recommended: /path/to/html/css)\n";
    exit;
}
if (!isset($argv[2])) {
    echo "Missing parameter 2: Search path for locating CSS usage (recommended: /path/to/html)\n";
    exit;
}
$css_root = $argv[1];
$site_root = $argv[2];

define("LOG_MSG_LEVEL",   3);

define("LOG_MSG_NONE",    0);
define("LOG_MSG_ERROR",   1);
define("LOG_MSG_WARNING", 2);
define("LOG_MSG_NOTICE",  3);
define("LOG_MSG_DEBUG",   4);

define("FOUND_CLASS_OR_ID", 1);
define("FOUND_BAREWORD", 2);

$SEARCH_CACHE = array('names' => array(),
                      'barewords' => array(),
                      'failed' => array());

$search_excludes = "--exclude=*.{css,jpg,png,gif}";

logmsg("Looking for unused CSS files.");
$files = get_file_paths($css_root);
logmsg("Found " . count($files) . " files.");
$unused_files = filter_unused_css_files($files, $site_root);

logmsg("Finding CSS selectors.");
$selectors = get_selectors_and_references($files);
#print_r(array_keys($selectors));
#exit;
$ignored_as_pseudo = filter_invalid_pseudo_classes($selectors);
$ignored_as_tags = filter_tag_selectors($selectors);

/*
    // SETUP TEST DATA - ONLY SEARCH A HANDFUL OF SELECTORS
    $sels = array();
    foreach ($selectors as $name => $properties) {
        $sels[$name] = $selectors[$name];
        if (count(array_keys($sels)) > 40) {
            break;
        }
    }
    $selectors = $sels;
*/

logmsg("Verifying used selectors.");
search_selectors($selectors, $site_root);

logmsg("Generating report.");
generate_report($selectors, $ignored_as_pseudo, $ignored_as_tags, $unused_files);

exit;




function generate_report($selectors, $ignored_as_pseudo, $ignored_as_tags, $unused_files)
{
    $hr = "\n-----------------------------------------------------------------------------\n";
    
    $sum_unused_files = count($unused_files);
    $sum_ignored_pseudo = count($ignored_as_pseudo);
    $sum_ignored_tags = count($ignored_as_tags);
    $sum_selectors = count($selectors);
    $sum_likely_unused = 0;
    $sum_possibly_unused = 0;
        
    echo $hr;
    echo "The following CSS files are possibly unused";
    echo $hr . "\n";
    foreach ($unused_files as $file) {
        echo "  $file\n";
    }
    echo "\n";
    
    echo $hr;
    echo "Selectors ignored as pseudo-classes";
    echo $hr . "\n";
    foreach (array_keys($ignored_as_pseudo) as $selector) {
        echo "  $selector\n";
    }
    echo "\n";
    
    echo $hr;
    echo "Selectors ignored as tags";
    echo $hr . "\n";
    foreach (array_keys($ignored_as_tags) as $selector) {
        echo "  $selector\n";
    }
    echo "\n";
    
    echo $hr;
    echo "Selectors ignored because they don't have any classes or IDs";
    echo $hr . "\n";
    foreach ($selectors as $selector => $props) {
        if ($props['properties']['no_classes_or_ids']) {
            echo "  $selector\n";
        }
    }
    echo "\n";
    
    echo $hr;
    echo "Selectors likely to be unused (not found in HTML tags or as barewords)\n * Name that was not matched is listed in parens.";
    echo $hr . "\n";
    foreach ($selectors as $selector => $props) {
        if ($props['properties']['missing_classes_or_ids'] && $props['properties']['missing_barewords']) {
            echo "  $selector   ({$props['properties']['failed_name']})\n";
            $sum_likely_unused++;
        }
    }
    echo "\n";
    
    echo $hr;
    echo "Selectors possibly unused (not found in HTML tags, but found as barewords)\n * Name that was not matched is listed in parens.";
    echo $hr . "\n";
    foreach ($selectors as $selector => $props) {
        if ($props['properties']['missing_classes_or_ids'] && !$props['properties']['missing_barewords']) {
            echo "  $selector   ({$props['properties']['failed_name']})\n";
            $sum_possibly_unused++;
        }
    }
    echo "\n";
    
    echo $hr;
    echo "Stats";
    echo $hr . "\n";
    echo "  Possibly unused CSS files: $sum_unused_files\n";
    echo "\n";
    
    $total = $sum_ignored_pseudo + $sum_ignored_tags + $sum_selectors;
    echo "  Total CSS selectors: $total\n";
    echo "  Likely unused:       " . $sum_likely_unused . ' (' . round(($sum_likely_unused / $total * 100), 1) . '%)' . "\n";
    echo "  Possibly unused:     " . $sum_possibly_unused . ' (' . round(($sum_possibly_unused / $total * 100), 1) . '%)' . "\n";
    
    echo "\n\n";
}

function get_file_paths($root)
{
    if (is_file($root)) {
        $files = array($root);
    } else {
        // Get directory contents   
        $find = 'find ' . escapeshellarg($root) . ' -name "*.css" ';
        $result = shell_exec($find);
        $files = explode("\n", trim($result));
    }
    return $files;
}

function get_selectors_and_references($files)
{
    $list = array();
    
    foreach ($files as $file) {
        $css = file_get_contents($file);
        $selectors = get_selectors($css);
        
        foreach ($selectors as $selector) {
            if (!isset($list[$selector])) {
                $list[$selector] = array('files' => array($file), 'properties' => array());
            } else {
                $list[$selector]['files'][] = $file;
            }
        }
    }
    
    return $list;
}

function get_selectors($css)
{
    $css = preg_replace("/\/\*.*\*\//sU", '', $css);
    $css = preg_replace("/{.*}/sU", '', $css);
    $css = str_replace(",", "\n", $css);
    $selectors = preg_split("/\s*\n\s*/", trim($css));
    for ($i=0; $i < count($selectors); $i++) {
        if (strpos($selectors[$i], "@") === 0) {
            #echo "Skip {$selectors[$i]}\n";
            array_splice($selectors, $i, 1);
            $i--;
        }
    }
    return $selectors;
}

function filter_tag_selectors(&$list)
{
    $filtered = array();
    
    foreach ($list as $selector => $value) {
        if (!preg_match("/[\.\#]+/", $selector)) {
            $filtered[$selector] = $value;
            unset($list[$selector]);
        }
    }
    
    return $filtered;
}

function filter_invalid_pseudo_classes(&$list)
{
    $filtered = array();
    
    foreach ($list as $selector => $value) {
        if (strpos($selector, ':') !== false 
            &&!preg_match("/\:(active|after|before|first-child|first-letter|first-line|focus|hover|lang|link|visited)/", $selector))
        {
            $filtered[$selector] = $value;
            unset($list[$selector]);
        }
    }
    
    return $filtered;
}

function filter_unused_css_files(&$css_files, $search_root)
{
    $filtered = array();
    
    for ($i = 0; $i < count($css_files); $i++) {
        $name = basename($css_files[$i]);
                
        logmsg("Searching for references to: $name", LOG_MSG_NOTICE);

        $cmd = "grep --silent --recursive --binary-files=without-match '$name' " . escapeshellarg($search_root);
        $found = system_get_success($cmd);
        if (!$found) {
            $filtered[] = $css_files[$i];
            array_splice($css_files, $i--, 1);
        }
    }
    
    return $filtered;
}

function search_selectors(&$selectors, $search_root)
{
    $last_sleep = time();
   
    $count = 0;
    $total = count(array_keys($selectors));
    foreach ($selectors as $selector => &$props) {

        $props['properties']['missing_barewords'] = 0;
        $props['properties']['missing_classes_or_ids'] = 0;
        $props['properties']['no_classes_or_ids'] = 0;
    
        logmsg("Inspecting selector: $selector ", LOG_MSG_NOTICE);
        
        if (preg_match_all("/(?:\.|\#)[\w\-]+/", $selector, $matches)) {
            
            // Search for any existance of class names or ids.
            foreach ($matches[0] as $class_or_id) {
                
                $status = find_name_in_files($class_or_id, $search_root);

                if ($status == FOUND_CLASS_OR_ID) {
                    continue;
                
                } else if ($status == FOUND_BAREWORD) {
                    $props['properties']['missing_classes_or_ids'] = 1;
                    $props['properties']['failed_name'] = $class_or_id;
                    break;
                                    
                } else {
                    $props['properties']['missing_barewords'] = 1;
                    $props['properties']['missing_classes_or_ids'] = 1;
                    $props['properties']['failed_name'] = $class_or_id;
                    break;
                }
            }

        } else {
            $props['properties']['no_classes_or_ids'] = 1;
        }
        
        #if (time() - $last_sleep > 10) {
        #    sleep(1);
        #    $last_sleep = time();
        #}
        
        if ($count % 50 == 0 && $count) {
            $perc = floor($count / $total * 100);
            logmsg("$perc% complete ($count/$total)");
        }
        $count++;
    }
    
}

function find_name_in_files($selector, $search_root)
{
    global $SEARCH_CACHE;
    
    // Make sure that selector looks valid
    if (strpos($selector, '#') !== 0 && strpos($selector, '.') !== 0) {
        logmsg("Unknown type for selector: '$selector'", LOG_MSG_ERROR);
        return false;
    }
    
    // Split name from selector.
    $name = substr($selector, 1);
    
    // Check search cache.
    if (isset($SEARCH_CACHE['names'][$selector])) {
        logmsg("Found in names cache.", LOG_MSG_DEBUG);
        return FOUND_CLASS_OR_ID;
    } else if (isset($SEARCH_CACHE['barewords'][$selector])) {
        logmsg("Found in barewords cache.", LOG_MSG_DEBUG);
        return FOUND_BAREWORD;
    } else if (isset($SEARCH_CACHE['failed'][$selector])) {
        logmsg("Found in failed cache.", LOG_MSG_DEBUG);
        return false;
    }

    // Search for references to class or ID.
    if (strpos($selector, '#') === 0) {
        $found = find_id($name, $search_root);
    } else if (strpos($selector, '.') === 0) {
        $found = find_class($name, $search_root);
    }
    
    if ($found) {
        $SEARCH_CACHE['names'][$selector] = 1;
        return FOUND_CLASS_OR_ID;
    } else if (find_bareword($name, $search_root)) {
        $SEARCH_CACHE['barewords'][$selector] = 1;
        return FOUND_BAREWORD;
    } else {
        $SEARCH_CACHE['failed'][$selector] = 1;
        return false;
    }
}

function find_id($id_name, $search_root)
{
    global $search_excludes;

    $searches = array();
    $searches[] = 'id=\x22[^\x22]*\b(?<!-)' . $id_name . '(?!-)\b[^\x22]*\x22';
    $searches[] = 'id=\x27[^\x27]*\b(?<!-)' . $id_name . '(?!-)\b[^\x27]*\x27';
    
    foreach ($searches as $search) {
        
        $cmd = "grep --silent --recursive --binary-files=without-match --perl-regexp $search_excludes '$search' " . escapeshellarg($search_root);
        $found = system_get_success($cmd);
        if ($found) {
            return true;
        }
    }
    
    return false;
}

function find_class($class_name, $search_root)
{
    global $search_excludes;

    $searches = array();
    $searches[] = 'class=\x22[^\x22]*\b(?<!-)' . $class_name . '(?!-)\b[^\x22]*\x22';
    $searches[] = 'class=\x27[^\x27]*\b(?<!-)' . $class_name . '(?!-)\b[^\x27]*\x27';

    foreach ($searches as $search) {
        $cmd = "grep --silent --recursive --binary-files=without-match --perl-regexp $search_excludes '$search' " . escapeshellarg($search_root);
        $found = system_get_success($cmd);
        if ($found) {
            return true;
        }
    }
    
    return false;
}

function find_bareword($word, $search_root)
{
    global $search_excludes;

    $pattern = '\b(?<![#\.])' . $word . '\b';
    $cmd = "grep --silent --recursive --binary-files=without-match --perl-regexp $search_excludes '$pattern' " . escapeshellarg($search_root);
    $found = system_get_success($cmd);
    if ($found) {
        return true;
    }
    return false;
}

function system_get_success($cmd)
{
    logmsg($cmd, LOG_MSG_DEBUG);
    system($cmd, $status);
    return ($status == 0) ? true : false;
}

function logmsg($message, $debug_level = 0)
{
    if ($debug_level <= LOG_MSG_LEVEL) {
        echo '[' . @date("r") . '] ' . $message . "\n";
    }
}

?>
