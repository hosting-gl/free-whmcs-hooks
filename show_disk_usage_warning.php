<?php
/**
 * Made by Hosting.gl ApS
 * This WHMCS hook provides disk usage warnings in the WHMCS admin area 
 * when viewing client profiles. It highlights accounts where disk usage 
 * exceeds a certain threshold, helping admins manage client resources effectively.
 */

add_hook('AdminAreaPage', 1, function ($vars) {
    $softThreshold = 80;
    $hardThreshold = 90;
    $pages = [
        'clientssummary',
        'clientsprofile',
        'clientscontacts',
        'clientsservices',
        'clientsdomains',
        'clientsbillableitems',
        'clientsinvoices',
        'clientsquotes',
        'clientstransactions',
        'clientsemails',
        'clientsnotes',
        'clientslog',
    ];
    if (!in_array($vars['filename'], $pages)) {
        return;
    }
    $extraVariables = [];
    try {
        $results = localAPI('GetClientsProducts', ['clientid' => $_GET['userid'], 'stats' => true]);
        $aboveHard = false;
        foreach ($results['products']['product'] as $product) {
            if ($product['disklimit'] === 0) {
                continue;
            }
            $percentage = ($product['diskusage'] / $product['disklimit']) * 100;
            if ($percentage <= $softThreshold) {
                continue;
            }
            if ($percentage >= $hardThreshold) {
                $aboveHard = true;
            }

            // Fetch the domain associated with this product
            $domainName = 'N/A';
            if (!empty($product['domain'])) {
                $domainName = $product['domain'];
            }

            $extraVariables['usageWarning'] .= sprintf(
                '<a href="clientsservices.php?userid=%d&productselect=%d">%s (%s): %d%%</a></br>',
                $_GET['userid'],
                $product['id'],
                $product['name'],
                $domainName,
                $percentage
            );
        }
        if (!empty($extraVariables['usageWarning'])) {
            $extraVariables['usageWarningClass'] = $aboveHard ? 'errorbox' : 'infobox';
        }
    } catch (Exception $exception) {
        // Handle exception if needed
    }
    return $extraVariables;
});
