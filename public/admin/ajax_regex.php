<?php

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'smarty.php';

use Blacklight\Regexes;
use Blacklight\Binaries;

// Login Check
$page = new AdminPage;

if (! $page->request->has('action')) {
    exit();
}

switch ($page->request->input('action')) {
    case 1:
        $id = (int) $page->request->input('col_id');
        (new Regexes(['Settings' => $page->settings]))->deleteRegex($id);
        echo "Regex $id deleted.";
        break;

    case 2:
        $id = (int) $page->request->input('bin_id');
        (new Binaries(['Settings' => $page->settings]))->deleteBlacklist($id);
        echo "Blacklist $id deleted.";
        break;
}
