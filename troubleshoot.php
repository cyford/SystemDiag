#!/usr/bin/env php
<?php

require_once 'LinuxTroubleshooter.php';

$troubleshooter = new LinuxTroubleshooter();

if ($argc > 1) {
    switch ($argv[1]) {
        case 'os':
            $troubleshooter->printOsVersion();
            break;
        case 'interfaces':
            $troubleshooter->printInterfaces();
            break;
        case 'gateway':
            $troubleshooter->printGateway();
            break;
        case 'dns':
            $troubleshooter->printDnsServers();
            break;
        case 'ping-gateway':
            $troubleshooter->pingGateway();
            break;
        case 'ping-external':
            $troubleshooter->pingExternal();
            break;
        case 'ping-dns':
            $troubleshooter->pingDnsResolution();
            break;
        case 'firewall':
            $troubleshooter->testFirewallPorts();
            break;
        case 'devices':
            $troubleshooter->displayConnectedDevices();
            break;
        case 'security':
            $troubleshooter->checkSecurityModules();
            break;
        case 'help':
            echo "Usage: php troubleshoot.php [command]\n";
            echo "Commands:\n";
            echo "  os           - Show OS information\n";
            echo "  interfaces   - Show network interfaces\n";
            echo "  gateway      - Show default gateway\n";
            echo "  dns          - Show DNS servers\n";
            echo "  ping-gateway - Ping default gateway\n";
            echo "  ping-external- Ping 8.8.8.8\n";
            echo "  ping-dns     - Ping google.com\n";
            echo "  firewall     - Test ports 80/443\n";
            echo "  security     - Check AppArmor/SELinux\n";
            echo "  devices      - Show connected devices\n";
            echo "  help         - Show this help\n";
            echo "  (no args)    - Run all tests\n";
            break;
        default:
            echo "Unknown command. Use 'help' for available commands.\n";
    }
} else {
    $troubleshooter->runAllTests();
}