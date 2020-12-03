<?php
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

// extract root_page is needed
$link = $this->GetParameter('link');
if ($link == 'root_page') {
    $this->setParameter('link',$this->config['root_page']);
}