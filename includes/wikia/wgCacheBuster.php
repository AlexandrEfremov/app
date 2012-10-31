<?php
/**
 * This file contains just the wgCacheBuster so that it can be included from
 * various code-paths, some of which don't load the rest of the MediaWiki stack.
 */

global $wgMedusaSlot;

$slot_name = 'code' . ($wgMedusaSlot == 1 ? '' : $wgMedusaSlot);
$cbFilePath = "/usr/wikia/deploy/$slot_name/src/wgCacheBuster.php";

require_once($cbFilePath);
